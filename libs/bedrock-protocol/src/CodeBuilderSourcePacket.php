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

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class CodeBuilderSourcePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::CODE_BUILDER_SOURCE_PACKET;

	private int $operation;
	private int $category;
	private string $value;
	private int $codeStatus;

	/**
	 * @generate-create-func
	 */
	public static function create(int $operation, int $category, string $value, int $codeStatus) : self{
		$result = new self;
		$result->operation = $operation;
		$result->category = $category;
		$result->value = $value;
		$result->codeStatus = $codeStatus;
		return $result;
	}

	public function getOperation() : int{ return $this->operation; }

	public function getCategory() : int{ return $this->category; }

	public function getValue() : string{ return $this->value; }

	public function getCodeStatus() : int{ return $this->codeStatus; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->operation = Byte::readUnsigned($in);
		$this->category = Byte::readUnsigned($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
			$this->codeStatus = Byte::readUnsigned($in);
		}else{
			$this->value = CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->operation);
		Byte::writeUnsigned($out, $this->category);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
			Byte::writeUnsigned($out, $this->codeStatus);
		}else{
			CommonTypes::putString($out, $this->value);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCodeBuilderSource($this);
	}
}
