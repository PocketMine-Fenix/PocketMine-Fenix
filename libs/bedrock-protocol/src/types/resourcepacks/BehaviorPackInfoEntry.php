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

namespace pocketmine\network\mcpe\protocol\types\resourcepacks;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class BehaviorPackInfoEntry{
	public function __construct(
		private string $packId,
		private string $version,
		private int $sizeBytes,
		private string $encryptionKey = "",
		private string $subPackName = "",
		private string $contentId = "",
		private bool $hasScripts = false,
		private bool $isAddonPack = false
	){}

	public function getPackId() : string{
		return $this->packId;
	}

	public function getVersion() : string{
		return $this->version;
	}

	public function getSizeBytes() : int{
		return $this->sizeBytes;
	}

	public function getEncryptionKey() : string{
		return $this->encryptionKey;
	}

	public function getSubPackName() : string{
		return $this->subPackName;
	}

	public function getContentId() : string{
		return $this->contentId;
	}

	public function hasScripts() : bool{
		return $this->hasScripts;
	}

	public function isAddonPack() : bool{ return $this->isAddonPack; }

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->packId);
		CommonTypes::putString($out, $this->version);
		LE::writeSignedLong($out, $this->sizeBytes);
		CommonTypes::putString($out, $this->encryptionKey);
		CommonTypes::putString($out, $this->subPackName);
		CommonTypes::putString($out, $this->contentId);
		CommonTypes::putBool($out, $this->hasScripts);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			CommonTypes::putBool($out, $this->isAddonPack);
		}
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$uuid = CommonTypes::getString($in);
		$version = CommonTypes::getString($in);
		$sizeBytes = LE::readSignedLong($in);
		$encryptionKey = CommonTypes::getString($in);
		$subPackName = CommonTypes::getString($in);
		$contentId = CommonTypes::getString($in);
		$hasScripts = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$isAddonPack = CommonTypes::getBool($in);
		}
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, $isAddonPack ?? false);
	}
}
