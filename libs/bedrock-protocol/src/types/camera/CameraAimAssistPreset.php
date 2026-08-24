<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\camera;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class CameraAimAssistPreset{

	/**
	 * @param string[] $liquidTargetingList
	 * @param CameraAimAssistPresetItemSettings[] $itemSettings
	 */
	public function __construct(
		private string $identifier,
		private string $categories,
		private CameraAimAssistPresetExclusionDefinition $exclusionSettings,
		private array $liquidTargetingList,
		private array $itemSettings,
		private ?string $defaultItemSettings,
		private ?string $defaultHandSettings,
	){}

	public function getIdentifier() : string{ return $this->identifier; }

	public function getCategories() : string{ return $this->categories; }

	public function getExclusionSettings() : CameraAimAssistPresetExclusionDefinition{ return $this->exclusionSettings; }

	/**
	 * @return string[]
	 */
	public function getLiquidTargetingList() : array{ return $this->liquidTargetingList; }

	/**
	 * @return CameraAimAssistPresetItemSettings[]
	 */
	public function getItemSettings() : array{ return $this->itemSettings; }

	public function getDefaultItemSettings() : ?string{ return $this->defaultItemSettings; }

	public function getDefaultHandSettings() : ?string{ return $this->defaultHandSettings; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$identifier = CommonTypes::getString($in);
		if($protocolId < ProtocolInfo::PROTOCOL_1_21_60){
			$categories = CommonTypes::getString($in);
		}

		$exclusionList = CameraAimAssistPresetExclusionDefinition::read($in, $protocolId);

		$liquidTargetingList = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$liquidTargetingList[] = CommonTypes::getString($in);
		}

		$itemSettings = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$itemSettings[] = CameraAimAssistPresetItemSettings::read($in);
		}

		$defaultItemSettings = CommonTypes::readOptional($in, fn() => CommonTypes::getString($in));
		$defaultHandSettings = CommonTypes::readOptional($in, fn() => CommonTypes::getString($in));

		return new self(
			$identifier,
			$categories ?? "",
			$exclusionList,
			$liquidTargetingList,
			$itemSettings,
			$defaultItemSettings,
			$defaultHandSettings
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->identifier);
		if($protocolId < ProtocolInfo::PROTOCOL_1_21_60){
			CommonTypes::putString($out, $this->categories);
		}

		$this->exclusionSettings->write($out, $protocolId);

		VarInt::writeUnsignedInt($out, count($this->liquidTargetingList));
		foreach($this->liquidTargetingList as $liquidTargeting){
			CommonTypes::putString($out, $liquidTargeting);
		}

		VarInt::writeUnsignedInt($out, count($this->itemSettings));
		foreach($this->itemSettings as $itemSetting){
			$itemSetting->write($out);
		}

		CommonTypes::writeOptional($out, $this->defaultItemSettings, CommonTypes::putString(...));
		CommonTypes::writeOptional($out, $this->defaultHandSettings, CommonTypes::putString(...));
	}
}
