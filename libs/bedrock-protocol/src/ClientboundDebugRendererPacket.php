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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\DebugMarkerData;
use function array_search;

class ClientboundDebugRendererPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_DEBUG_RENDERER_PACKET;

	public const TYPE_CLEAR = "cleardebugmarkers";
	public const TYPE_ADD_CUBE = "adddebugmarkercube";

	private const TRANSLATION = [
		self::TYPE_CLEAR => 1,
		self::TYPE_ADD_CUBE => 2,
	];

	private string $type;
	private ?DebugMarkerData $data = null;

	private static function base(string $type) : self{
		$result = new self;
		$result->type = $type;
		return $result;
	}

	public static function clear() : self{ return self::base(self::TYPE_CLEAR); }

	public static function addCube(DebugMarkerData $data) : self{
		$result = self::base(self::TYPE_ADD_CUBE);
		$result->data = $data;
		return $result;
	}

	private function getTypeFromId(int $typeId) : string{
		$type = array_search($typeId, self::TRANSLATION, true);
		if($type === false){
			throw new \InvalidArgumentException("Invalid type id: $typeId");
		}
		return $type;
	}

	public function getType() : string{ return $this->type; }

	public function getData() : ?DebugMarkerData{ return $this->data; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$this->type = CommonTypes::getString($in);
			$this->data = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => DebugMarkerData::read($in, $protocolId));
		}else{
			$this->type = $this->getTypeFromId(LE::readUnsignedInt($in));
			if($this->type === self::TYPE_ADD_CUBE){
				$this->data = DebugMarkerData::read($in, $protocolId);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::putString($out, $this->type);
			CommonTypes::writeOptional($out, $this->data, fn(ByteBufferWriter $out, DebugMarkerData $data) => $data->write($out, $protocolId));
		}else{
			LE::writeUnsignedInt($out, self::TRANSLATION[$this->type] ?? throw new \InvalidArgumentException("Invalid action type for protocol $protocolId: $this->type"));
			if($this->type === self::TYPE_ADD_CUBE){
				if($this->data === null){
					throw new \InvalidArgumentException("DebugMarkerData must be set for type " . self::TYPE_ADD_CUBE);
				}
				$this->data->write($out, $protocolId);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundDebugRenderer($this);
	}
}
