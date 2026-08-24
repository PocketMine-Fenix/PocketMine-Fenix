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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\data\JavaWorldData;
use pocketmine\world\format\io\LoadedChunkData;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;

class Anvil extends RegionWorldProvider{
	use LegacyAnvilChunkTrait{
		LegacyAnvilChunkTrait::deserializeChunk as legacyDeserializeChunk;
	}

	private ?JavaBlockStateTranslator $modernTranslator = null;

	/**
	 * Routes each chunk to the correct reader based on its own contents:
	 * modern (1.18+) chunks carry a "sections" list with block_states compounds,
	 * while legacy chunks use Level/Sections with Blocks byte arrays.
	 */
	public function deserializeChunk(string $data, \Logger $logger) : ?LoadedChunkData{
		return $this->legacyDeserializeChunk($data, $logger);
	}

	/**
	 * Called by LegacyAnvilChunkTrait when a modern (1.18+) chunk is detected.
	 */
	protected function deserializeModernJavaChunk(string $data, \Logger $logger) : LoadedChunkData{
		return ModernJavaAnvilDeserializer::deserialize($data, $this->getModernTranslator());
	}

	protected function getModernTranslator() : JavaBlockStateTranslator{
		if($this->modernTranslator === null){
			$this->modernTranslator = new JavaBlockStateTranslator();
		}
		return $this->modernTranslator;
	}

	protected function deserializeSubChunk(CompoundTag $subChunk, PalettedBlockArray $biomes3d, \Logger $logger) : SubChunk{
		return new SubChunk(Block::EMPTY_STATE_ID, [$this->palettizeLegacySubChunkYZX(
			self::readFixedSizeByteArray($subChunk, "Blocks", 4096),
			self::readFixedSizeByteArray($subChunk, "Data", 2048),
			$logger
		)], $biomes3d);
		//ignore legacy light information
	}

	protected static function getRegionFileExtension() : string{
		return "mca";
	}

	protected static function getPcWorldFormatVersion() : int{
		return 19133;
	}

	public function getWorldMinY() : int{
		return $this->isModernHeightWorld() ? -64 : 0;
	}

	public function getWorldMaxY() : int{
		//TODO: add world height options
		return $this->isModernHeightWorld() ? 320 : 256;
	}

	private function isModernHeightWorld() : bool{
		$worldData = $this->getWorldData();
		return $worldData instanceof JavaWorldData && $worldData->getDataVersion() >= JavaWorldData::MIN_PALETTE_COMPOUND_DATA_VERSION;
	}
}
