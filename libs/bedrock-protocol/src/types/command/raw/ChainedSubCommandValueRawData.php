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

namespace pocketmine\network\mcpe\protocol\types\command\raw;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class ChainedSubCommandValueRawData{

	public function __construct(
		private int $nameIndex,
		private int $type
	){}

	public function getNameIndex() : int{ return $this->nameIndex; }

	public function getType() : int{ return $this->type; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$nameIndex = VarInt::readUnsignedInt($in);
			$type = VarInt::readUnsignedInt($in);
		}else{
			$nameIndex = LE::readUnsignedShort($in);
			$type = LE::readUnsignedShort($in);
		}

		return new self($nameIndex, $type);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			VarInt::writeUnsignedInt($out, $this->nameIndex);
			VarInt::writeUnsignedInt($out, $this->type);
		}else{
			LE::writeUnsignedShort($out, $this->nameIndex);
			LE::writeUnsignedShort($out, $this->type);
		}
	}
}
