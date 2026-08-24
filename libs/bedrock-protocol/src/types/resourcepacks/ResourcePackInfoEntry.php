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
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ResourcePackInfoEntry{
	public function __construct(
		private UuidInterface $packId,
		private string $version,
		private int $sizeBytes,
		private string $encryptionKey = "",
		private string $subPackName = "",
		private string $contentId = "",
		private bool $hasScripts = false,
		private bool $isAddonPack = false,
		private bool $isRtxCapable = false,
		private string $cdnUrl = ""
	){}

	public function getPackId() : UuidInterface{
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

	public function isRtxCapable() : bool{ return $this->isRtxCapable; }

	public function getCdnUrl() : string{ return $this->cdnUrl; }

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
			CommonTypes::putUUID($out, $this->packId);
		}else{
			CommonTypes::putString($out, $this->packId->toString());
		}
		CommonTypes::putString($out, $this->version);
		LE::writeUnsignedLong($out, $this->sizeBytes);
		CommonTypes::putString($out, $this->encryptionKey);
		CommonTypes::putString($out, $this->subPackName);
		CommonTypes::putString($out, $this->contentId);
		CommonTypes::putBool($out, $this->hasScripts);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			CommonTypes::putBool($out, $this->isAddonPack);
		}
		CommonTypes::putBool($out, $this->isRtxCapable);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			CommonTypes::putString($out, $this->cdnUrl);
		}
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_50){
			$uuid = CommonTypes::getUUID($in);
		}else{
			$uuid = Uuid::fromString(CommonTypes::getString($in));
		}
		$version = CommonTypes::getString($in);
		$sizeBytes = LE::readUnsignedLong($in);
		$encryptionKey = CommonTypes::getString($in);
		$subPackName = CommonTypes::getString($in);
		$contentId = CommonTypes::getString($in);
		$hasScripts = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$isAddonPack = CommonTypes::getBool($in);
		}
		$rtxCapable = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			$cdnUrl = CommonTypes::getString($in);
		}
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, $isAddonPack ?? false, $rtxCapable, $cdnUrl ?? "");
	}
}
