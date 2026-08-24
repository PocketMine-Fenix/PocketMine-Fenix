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
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class InteractPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::INTERACT_PACKET;

	public const ACTION_LEAVE_VEHICLE = 3;
	public const ACTION_MOUSEOVER = 4;
	public const ACTION_OPEN_NPC = 5;
	public const ACTION_OPEN_INVENTORY = 6;

	public int $action;
	public int $targetActorRuntimeId;
	public ?Vector3 $position = null;

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->action = Byte::readUnsigned($in);
		$this->targetActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$this->position = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
		}elseif($this->action === self::ACTION_MOUSEOVER || $this->action === self::ACTION_LEAVE_VEHICLE){
			$this->position = CommonTypes::getVector3($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->action);
		CommonTypes::putActorRuntimeId($out, $this->targetActorRuntimeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::writeOptional($out, $this->position, CommonTypes::putVector3(...));
		}elseif($this->action === self::ACTION_MOUSEOVER || $this->action === self::ACTION_LEAVE_VEHICLE){
			CommonTypes::putVector3($out, $this->position ?? throw new \InvalidArgumentException("Position must be set for this action"));
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleInteract($this);
	}
}
