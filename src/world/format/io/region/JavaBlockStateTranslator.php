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

use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use function count;
use function implode;
use function in_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function ksort;
use function str_starts_with;
use function strtolower;

/**
 * Translates Java Edition blockstate identifiers (name + properties) into
 * Fenix Bedrock canonical blockstate IDs.
 *
 * Modern Java and Bedrock share most block names and property names/values,
 * so translation works by finding same-name candidates in the Bedrock state
 * dictionary and choosing the variant that matches the most Java properties.
 * Java-only properties are ignored; blocks with no name match fall back to
 * the info_update placeholder so gaps are visible instead of silent holes.
 */
final class JavaBlockStateTranslator{
	private const FALLBACK_NAME = 'minecraft:air';

	private BlockStateDictionary $dictionary;
	/** @var array<string, list<int>> */
	private array $candidatesByName = [];
	/** @var array<string, int> */
	private array $cache = [];

	public int $exactMatches = 0;
	public int $defaultVariantMatches = 0;
	public int $fallbacks = 0;

	public function __construct(?BlockStateDictionary $dictionary = null){
		$this->dictionary = $dictionary ?? BlockStateDictionary::loadFromString(
			Filesystem::fileGetContents(\pocketmine\BEDROCK_DATA_PATH . '/canonical_block_states.nbt'),
			Filesystem::fileGetContents(\pocketmine\BEDROCK_DATA_PATH . '/block_state_meta_map.json')
		);
		foreach($this->dictionary->getStates() as $stateId => $entry){
			$this->candidatesByName[$entry->getStateName()][] = $stateId;
		}
	}

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
		$candidates = $this->candidatesByName[$name] ?? null;
		if($candidates === null || count($candidates) === 0){
			$this->fallbacks++;
			return $this->fallbackStateId();
		}

		$wanted = [];
		foreach(Utils::stringifyKeys($properties) as $p => $v){
			$wanted['minecraft:' . $p] = $v;
		}

		$bestStateId = null;
		$bestScore = -1;
		foreach($candidates as $stateId){
			$data = $this->dictionary->generateDataFromStateId($stateId);
			if($data === null){
				continue;
			}
			$score = 0;
			$mismatch = false;
			foreach(Utils::stringifyKeys($data->getStates()) as $k => $tag){
				$key = 'minecraft:' . $k;
				if(!isset($wanted[$key])){
					continue; //Bedrock property with no Java counterpart - ignore for scoring
				}
				$value = self::tagValueToString($tag);
				if($wanted[$key] === $value || (self::isBooleanLike($value) && self::isBooleanLike($wanted[$key]) && ($value === 'true') === ($wanted[$key] === 'true'))){
					$score++;
				}else{
					$mismatch = true;
				}
			}
			if(!$mismatch && $score === count($data->getStates())){
				$this->exactMatches++;
				return $stateId;
			}
			if(!$mismatch && $score > $bestScore){
				$bestScore = $score;
				$bestStateId = $stateId;
			}
		}

		if($bestStateId !== null){
			$this->defaultVariantMatches++;
			return $bestStateId;
		}
		//no properties matched at all - use the default (first) variant of this block
		$this->defaultVariantMatches++;
		return $candidates[0];
	}

	private function fallbackStateId() : int{
		$candidates = $this->candidatesByName[self::FALLBACK_NAME] ?? null;
		if($candidates === null || count($candidates) === 0){
			return 0; //air as absolute last resort
		}
		return $candidates[0];
	}

	private static function isBooleanLike(string $value) : bool{
		return in_array(strtolower($value), ['true', 'false', '1', '0'], true);
	}

	/**
	 * Normalises NBT tag values (ByteTag 0/1 booleans, IntTag numbers, StringTag
	 * text) into comparable canonical strings.
	 */
	private static function tagValueToString(\pocketmine\nbt\tag\Tag $tag) : string{
		$value = $tag->getValue();
		if(is_bool($value)){
			return $value ? 'true' : 'false';
		}
		if(is_int($value)){
			//Bedrock encodes booleans as ints; prefer the boolean form when the value fits
			if($value === 0 || $value === 1){
				return $value === 1 ? 'true' : 'false';
			}
			return (string) $value;
		}
		if(is_string($value)){
			return $value;
		}
		throw new \InvalidArgumentException("Unexpected NBT value type " . get_debug_type($value));
	}
}
