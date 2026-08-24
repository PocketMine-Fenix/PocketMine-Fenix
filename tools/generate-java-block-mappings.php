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

namespace pocketmine\build\generate_java_block_mappings;

use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Filesystem;
use function count;
use function dirname;
use function fwrite;
use function gzdecode;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function rtrim;
use function strlen;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const STDERR;

require dirname(__DIR__) . '/vendor/autoload.php';

/*
 * Generates resources/java-mappings/java_block_renames.json - a mapping of Java Edition
 * block identifier -> Bedrock identifier for every block whose name differs
 * between editions (e.g. grass_block -> grass, water -> flowing_water).
 *
 * Method: PrismarineJS minecraft-data gives us each Java block together with the
 * state id of its DEFAULT variant. The Geyser mappings nbt is indexed by exactly
 * those Java state ids and carries a bedrock_identifier override whenever the
 * Bedrock name differs. Names are therefore anchored per-block, which is all we
 * need because renames never vary between variants of the same block.
 *
 * Inputs (bundled under resources/java-mappings/):
 *  - java_blocks_prismarine.json    (PrismarineJS minecraft-data, MIT)
 *  - java_state_mappings_geyser.nbt (GeyserMC mappings, MIT)
 */

if(!isset($argc, $argv) || $argc !== 2){
	fwrite(STDERR, "Usage: php generate-java-block-mappings.php <resources/java-mappings directory>\n");
	exit(1);
}
$dataPath = rtrim($argv[1], '/');

fwrite(STDERR, "Loading Prismarine Java blocks...\n");
$javaBlocks = json_decode(Filesystem::fileGetContents($dataPath . '/java_blocks_prismarine.json'), true, flags: JSON_THROW_ON_ERROR);
if(!is_array($javaBlocks)){
	throw new \RuntimeException("Invalid prismarine JSON");
}

fwrite(STDERR, "Loading Geyser state overrides...\n");
$nbt = new BigEndianNbtSerializer();
$geyserRaw = Filesystem::fileGetContents($dataPath . '/java_state_mappings_geyser.nbt');
$geyserDecompressed = gzdecode($geyserRaw);
if($geyserDecompressed === false){
	throw new \RuntimeException("Failed to decompress Geyser mappings");
}
$geyserRoot = $nbt->read($geyserDecompressed)->mustGetCompoundTag();
$geyserList = $geyserRoot->getListTag('bedrock_mappings');
if($geyserList === null){
	throw new \RuntimeException("Geyser mappings file missing 'bedrock_mappings' list");
}
$geyserCount = $geyserList->count();

$renames = [];
$matchedBlocks = 0;
foreach($javaBlocks as $block){
	if(!is_array($block)){
		continue;
	}
	$javaName = isset($block['name']) && is_string($block['name']) ? $block['name'] : null;
	$defaultState = isset($block['defaultState']) && is_int($block['defaultState']) ? $block['defaultState'] : null;
	if($javaName === null || $defaultState === null){
		continue;
	}
	$matchedBlocks++;

	$entry = $defaultState >= 0 && $defaultState < $geyserCount ? $geyserList->get($defaultState) : null;
	$bedrockIdentifier = null;
	if($entry instanceof CompoundTag){
		$bi = $entry->getTag('bedrock_identifier');
		if($bi !== null && is_string($biValue = $bi->getValue())){
			$bedrockIdentifier = $biValue;
		}
	}

	$javaId = 'minecraft:' . $javaName;
	$bedrockId = $bedrockIdentifier !== null ? 'minecraft:' . $bedrockIdentifier : $javaId;
	if($bedrockId !== $javaId){
		$renames[$javaId] = $bedrockId;
	}
}

fwrite(STDERR, "blocks scanned=$matchedBlocks renames=" . count($renames) . "\n");
ksort($renames);

$json = json_encode(
	[
		'_meta' => [
			'generator' => 'PocketMine-Fenix generate-java-block-mappings.php',
			'sources' => ['PrismarineJS minecraft-data', 'GeyserMC mappings'],
			'description' => 'Java block identifier -> Bedrock identifier for blocks whose name differs between editions',
		],
		'renames' => $renames,
	],
	flags: JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
Filesystem::safeFilePutContents($dataPath . '/java_block_renames.json', $json . "\n");
fwrite(STDERR, "Written " . strlen($json) . " bytes to java_block_renames.json\n");
