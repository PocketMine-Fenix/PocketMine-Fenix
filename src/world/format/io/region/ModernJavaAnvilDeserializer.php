<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\world\format\io\region;

use pocketmine\block\Block;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongArrayTag;
use pocketmine\utils\Utils;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\exception\CorruptedChunkException;
use pocketmine\world\format\io\LoadedChunkData;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function array_fill;
use function array_keys;
use function bindec;
use function ceil;
use function count;
use function decbin;
use function implode;
use function is_string;
use function log;
use function max;
use function pack;
use function str_pad;
use function substr;
use function zlib_decode;
use const STR_PAD_LEFT;

/**
 * Deserialises post-flattening Java Edition Anvil chunks (Minecraft 1.18+,
 * DataVersion >= 2724): sections carry block_states compounds with palettes,
 * world height -64..319.
 *
 * Blockstates are translated at palette level (one lookup per unique Java
 * blockstate instead of 4096 per section).
 */
final class ModernJavaAnvilDeserializer{
	public const WORLD_MIN_Y = -64;
	public const WORLD_MAX_Y = 320;

	private function __construct(
		private JavaBlockStateTranslator $translator
	){}

	public static function deserialize(string $data, JavaBlockStateTranslator $translator) : LoadedChunkData{
		return (new self($translator))->parse($data);
	}

	private function parse(string $data) : LoadedChunkData{
		$decompressed = @zlib_decode($data);
		if($decompressed === false){
			throw new CorruptedChunkException("Failed to decompress chunk NBT");
		}
		$nbt = new BigEndianNbtSerializer();
		try{
			$root = $nbt->read($decompressed)->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw new CorruptedChunkException($e->getMessage(), 0, $e);
		}

		//1.16- wraps fields inside "Level"; 1.17+ keeps most fields at root level
		$chunk = ($tmp = $root->getTag('Level')) instanceof CompoundTag ? $tmp : $root;

		$sectionsTag = $chunk->getListTag('sections', CompoundTag::class);
		if($sectionsTag === null){
			throw new CorruptedChunkException("'sections' key is missing from modern Java chunk NBT");
		}

		$biomes3d = new PalettedBlockArray(BiomeIds::OCEAN);
		$subChunks = [];
		for($y = Chunk::MIN_SUBCHUNK_INDEX; $y <= Chunk::MAX_SUBCHUNK_INDEX; ++$y){
			$subChunks[$y] = new SubChunk(Block::EMPTY_STATE_ID, [], clone $biomes3d);
		}

		foreach($sectionsTag as $section){
			$sectionY = $section->getByte('Y');
			if($sectionY < Chunk::MIN_SUBCHUNK_INDEX || $sectionY > Chunk::MAX_SUBCHUNK_INDEX){
				continue;
			}
			$statesTag = $section->getTag('block_states');
			if(!$statesTag instanceof CompoundTag){
				continue; //empty section
			}
			$paletteList = $statesTag->getListTag('palette', CompoundTag::class);
			if($paletteList === null || $paletteList->count() === 0){
				continue;
			}

			//translate the Java palette (name+properties -> Fenix state ids)
			$translatedPalette = [];
			$decodeErrors = [];
			/** @var list<CompoundTag> $paletteEntries */
			$paletteEntries = $paletteList->getValue();
			foreach($paletteEntries as $entry){
				if(!$entry instanceof CompoundTag){
					continue;
				}
				$name = $entry->getString('Name');
				$props = [];
				$propsTag = $entry->getTag('Properties');
				if($propsTag instanceof CompoundTag){
					foreach(Utils::stringifyKeys($propsTag->getValue()) as $k => $v){
						if(is_string($v)){
							$props[$k] = $v;
						}
					}
				}
				try{
					$translatedPalette[] = $this->translator->translate($name, $props);
				}catch(\Throwable $e){
					$decodeErrors[] = "$name: " . $e->getMessage();
					$translatedPalette[] = $this->translator->translate('minecraft:info_update', []);
				}
			}
			if(count($decodeErrors) > 0){
				throw new CorruptedChunkException("Errors translating section y=" . ($sectionY << 4) . ":\n - " . implode("\n - ", $decodeErrors));
			}

			$dataTag = $section->getTag('data');
			$javaIndices = $dataTag instanceof LongArrayTag
				? self::unpackIndices($dataTag->getValue(), count($translatedPalette))
				: array_fill(0, 4096, 0);

			//deduplicate the translated palette and rebuild index list
			$dedupPalette = [];
			$remap = [];
			$newIndices = [];
			foreach($javaIndices as $i => $oldIndex){
				$id = $translatedPalette[$oldIndex];
				if(!isset($dedupPalette[$id])){
					$dedupPalette[$id] = count($dedupPalette);
				}
				$newIndices[$i] = $dedupPalette[$id];
			}
			$newPalette = array_keys($dedupPalette);

			$bitsPerBlock = max(4, (int) ceil(log(max(2, count($newPalette)), 2)));
			$subChunks[$sectionY] = new SubChunk(
				Block::EMPTY_STATE_ID,
				[PalettedBlockArray::fromData($bitsPerBlock, self::packIndices($newIndices, $bitsPerBlock), $newPalette)],
				clone $biomes3d
			);
		}

		return new LoadedChunkData(
			data: new ChunkData(
				$subChunks,
				true, //treat as populated so generation does not pile features on top of imported terrain
				[], //entities live in separate entity region files since 1.17 - not migrated yet
				[], //TODO: tile entities
			),
			upgraded: true,
			fixerFlags: LoadedChunkData::FIXER_FLAG_ALL
		);
	}

	/**
	 * Unpacks a Java Edition packed long array into a flat list of 4096 palette
	 * indices. Java packs entries LSB-first across signed 64-bit longs with no
	 * per-entry padding.
	 *
	 * @param int[] $longs
	 *
	 * @return int[]
	 * @phpstan-return list<int>
	 */
	private static function unpackIndices(array $longs, int $paletteCount) : array{
		$total = 4096;
		if($paletteCount <= 1 || count($longs) === 0){
			return array_fill(0, $total, 0);
		}
		$bits = max(4, (int) ceil(log(max(2, $paletteCount), 2)));
		$entriesPerLong = (int) (64 / $bits);
		$mask = (1 << $bits) - 1;

		$result = [];
		$index = 0;
		foreach($longs as $long){
			//PHP has no unsigned shift-right for 64-bit ints; use the bit string
			$bitsStr = str_pad(decbin($long % (2 ** 64)), 64, '0', STR_PAD_LEFT);
			for($slot = 0; $slot < $entriesPerLong && $index < $total; ++$slot){
				$hi = 64 - ($slot + 1) * $bits;
				$result[] = (int) bindec(substr($bitsStr, $hi, $bits));
				++$index;
			}
			if($index >= $total){
				break;
			}
		}
		while(count($result) < $total){
			$result[] = 0;
		}
		return $result;
	}

	/**
	 * Packs flat indices into chunkutils2's word-array representation
	 * (little-endian 32-bit words, entries LSB-first, no cross-word spilling).
	 *
	 * @param int[] $indices
	 */
	private static function packIndices(array $indices, int $bits) : string{
		$out = '';
		$currentWord = 0;
		$posInWord = 0;
		foreach($indices as $index){
			$currentWord |= $index << $posInWord;
			$posInWord += $bits;
			if($posInWord >= 32){
				$out .= pack('V', $currentWord & 0xFFFFFFFF);
				$currentWord = 0;
				$posInWord = 0;
			}
		}
		if($posInWord > 0){
			$out .= pack('V', $currentWord & 0xFFFFFFFF);
		}
		return $out;
	}
}
