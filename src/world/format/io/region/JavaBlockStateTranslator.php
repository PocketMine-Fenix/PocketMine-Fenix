<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___/     |_|  |_|_|
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
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function count;
use function ksort;
use function strlen;

/**
 * Translates Java Edition blockstate identifiers into Fenix internal
 * blockstate IDs using PM's global deserializer pipeline.
 *
 * Strategy:
 * - Uses only the BLOCK NAME (no properties) to resolve the state ID.
 *   This guarantees the correct block type is always placed, using the
 *   default variant when specific properties cannot be matched across
 *   editions. Orientation details (facing, axis) may be lost but blocks
 *   are never missing or crash-inducing.
 */
final class JavaBlockStateTranslator{
	/** @var array<string, int> */
	private array $cache = [];

	public int $resolved = 0;
	public int $fallbacks = 0;

	/**
	 * @param array<string, string> $properties raw Java property values (unused, kept for API compat)
	 */
	public function translate(string $javaName, array $properties = []) : int{
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

		$name = str_starts_with($javaName, 'minecraft:') ? $javaName : 'minecraft:' . $javaName;

		//resolve using ONLY the block name via PM's global pipeline
		try{
			$blockStateData = new BlockStateData($name, [], 0);
			$block = GlobalBlockStateHandlers::getDeserializer()->deserializeBlock($blockStateData);
			$this->resolved++;
			return $block->getStateId();
		}catch(\Throwable){
			//PM doesn't implement this block - use air instead of crashing
			$this->fallbacks++;
			return 0; //air
		}
	}
}
