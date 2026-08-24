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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class FullContainerName{
	public function __construct(
		private int $containerId,
		private ?int $dynamicId = null
	){}

	public function getContainerId() : int{ return $this->containerId; }

	public function getDynamicId() : ?int{ return $this->dynamicId; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$containerId = Byte::readUnsigned($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			$dynamicId = CommonTypes::readOptional($in, LE::readUnsignedInt(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$dynamicId = LE::readUnsignedInt($in);
		}
		return new self($containerId, $dynamicId ?? null);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->containerId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_30){
			CommonTypes::writeOptional($out, $this->dynamicId, LE::writeUnsignedInt(...));
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			LE::writeUnsignedInt($out, $this->dynamicId ?? 0);
		}
	}
}
