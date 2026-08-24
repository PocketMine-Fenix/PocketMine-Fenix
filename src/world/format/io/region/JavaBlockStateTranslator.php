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

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function count;
use function implode;
use function ksort;
use function str_starts_with;

/**
 * Translates Java Edition blockstate identifiers (name + properties) into
 * Fenix internal blockstate IDs using PM's global deserializer pipeline.
 *
 * Java and Bedrock share most block names and property names/values, so
 * translation builds a BlockStateData with the same name/props and lets the
 * global deserializer resolve it to the correct runtime ID. Blocks that PM
 * cannot deserialise fall back to air so they never crash the server.
 */
final class JavaBlockStateTranslator{
	/** @var array<string, int> */
	private array $cache = [];

	public int $resolved = 0;
	public int $fallbacks = 0;

	/**
	 * @param array<string, string> $properties raw Java property values (unprefixed names)
	 */
	public function translate(string $javaName, array $properties) : int{
		if(count($properties) > 0){
			$parts = [];
			foreach(Utils::stringifyKeys($properties) as $p => $v){
				$parts[] = $p . '=' . $v;
			}
			ksort($parts);
			$javaName .= '[' . implode(',', $parts) . ']';
		}
		if(isset($this->cache[$javaName])){
			return $this->cache[$javaName];
		}
		return $this->cache[$javaName] = $this->translateInternal($javaName, $properties);
	}

	/**
	 * @param array<string, string> $properties
	 */
	private function translateInternal(string $fullName, array $properties) : int{
		$name = str_starts_with($fullName, 'minecraft:') ? $fullName : 'minecraft:' . $fullName;

		//build Bedrock-style BlockStateData from the Java state (name and prop keys use minecraft: prefix)
		$bedrockProps = [];
		foreach(Utils::stringifyKeys($properties) as $p => $v){
			$bedrockProps['minecraft:' . $p] = new StringTag($v);
		}
		$stateData = new BlockStateData($name, $bedrockProps, 0);

		try{
			$block = GlobalBlockStateHandlers::getDeserializer()->deserializeBlock($stateData);
			$this->resolved++;
			return $block->getStateId();
		}catch(\Throwable){
			//PM doesn't implement this block yet - use air instead of crashing
			$this->fallbacks++;
			return 0; //air
		}
	}
}
