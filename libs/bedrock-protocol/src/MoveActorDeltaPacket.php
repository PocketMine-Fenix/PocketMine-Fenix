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

class MoveActorDeltaPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_PITCH = 0x08;
	public const FLAG_HAS_YAW = 0x10;
	public const FLAG_HAS_HEAD_YAW = 0x20;
	public const FLAG_GROUND = 0x40;
	public const FLAG_TELEPORT = 0x80;
	public const FLAG_FORCE_MOVE_LOCAL_ENTITY = 0x100;

	public int $actorRuntimeId;
	public ?float $xPos = null;
	public ?float $yPos = null;
	public ?float $zPos = null;
	public ?float $pitch = null;
	public ?float $yaw = null;
	public ?float $headYaw = null;
	public bool $onGround = false;
	public bool $teleport = false; //force move in the docs
	public bool $forceMoveLocalEntity = false; //force move local entity in the docs
	public bool $forceCompletion = false;

	/**
	 * @generate-create-func
	 */
	public static function create(
		int $actorRuntimeId,
		?float $xPos,
		?float $yPos,
		?float $zPos,
		?float $pitch,
		?float $yaw,
		?float $headYaw,
		bool $onGround,
		bool $teleport,
		bool $forceMoveLocalEntity,
		bool $forceCompletion,
	) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->xPos = $xPos;
		$result->yPos = $yPos;
		$result->zPos = $zPos;
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->headYaw = $headYaw;
		$result->onGround = $onGround;
		$result->teleport = $teleport;
		$result->forceMoveLocalEntity = $forceMoveLocalEntity;
		$result->forceCompletion = $forceCompletion;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->xPos = CommonTypes::readOptional($in, LE::readFloat(...));
			$this->yPos = CommonTypes::readOptional($in, LE::readFloat(...));
			$this->zPos = CommonTypes::readOptional($in, LE::readFloat(...));
			$this->pitch = CommonTypes::readOptional($in, CommonTypes::getRotationByte(...));
			$this->yaw = CommonTypes::readOptional($in, CommonTypes::getRotationByte(...));
			$this->headYaw = CommonTypes::readOptional($in, CommonTypes::getRotationByte(...));
			$this->onGround = CommonTypes::getBool($in);
			$this->teleport = CommonTypes::getBool($in);
			$this->forceMoveLocalEntity = CommonTypes::getBool($in);
			$this->forceCompletion = CommonTypes::getBool($in);
		}else{
			$flags = LE::readUnsignedShort($in);
			$this->xPos = ($flags & self::FLAG_HAS_X) !== 0 ? LE::readFloat($in) : null;
			$this->yPos = ($flags & self::FLAG_HAS_Y) !== 0 ? LE::readFloat($in) : null;
			$this->zPos = ($flags & self::FLAG_HAS_Z) !== 0 ? LE::readFloat($in) : null;
			$this->pitch = ($flags & self::FLAG_HAS_PITCH) !== 0 ? CommonTypes::getRotationByte($in) : null;
			$this->yaw = ($flags & self::FLAG_HAS_YAW) !== 0 ? CommonTypes::getRotationByte($in) : null;
			$this->headYaw = ($flags & self::FLAG_HAS_HEAD_YAW) !== 0 ? CommonTypes::getRotationByte($in) : null;
			$this->onGround = ($flags & self::FLAG_GROUND) !== 0;
			$this->teleport = ($flags & self::FLAG_TELEPORT) !== 0;
			$this->forceMoveLocalEntity = ($flags & self::FLAG_FORCE_MOVE_LOCAL_ENTITY) !== 0;
			$this->forceCompletion = false;
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->xPos, LE::writeFloat(...));
			CommonTypes::writeOptional($out, $this->yPos, LE::writeFloat(...));
			CommonTypes::writeOptional($out, $this->zPos, LE::writeFloat(...));
			CommonTypes::writeOptional($out, $this->pitch, CommonTypes::putRotationByte(...));
			CommonTypes::writeOptional($out, $this->yaw, CommonTypes::putRotationByte(...));
			CommonTypes::writeOptional($out, $this->headYaw, CommonTypes::putRotationByte(...));
			CommonTypes::putBool($out, $this->onGround);
			CommonTypes::putBool($out, $this->teleport);
			CommonTypes::putBool($out, $this->forceMoveLocalEntity);
			CommonTypes::putBool($out, $this->forceCompletion);
		}else{
			$flags = 0;
			$flags |= $this->xPos !== null ? self::FLAG_HAS_X : 0;
			$flags |= $this->yPos !== null ? self::FLAG_HAS_Y : 0;
			$flags |= $this->zPos !== null ? self::FLAG_HAS_Z : 0;
			$flags |= $this->pitch !== null ? self::FLAG_HAS_PITCH : 0;
			$flags |= $this->yaw !== null ? self::FLAG_HAS_YAW : 0;
			$flags |= $this->headYaw !== null ? self::FLAG_HAS_HEAD_YAW : 0;
			$flags |= $this->onGround ? self::FLAG_GROUND : 0;
			$flags |= $this->teleport ? self::FLAG_TELEPORT : 0;
			$flags |= $this->forceMoveLocalEntity ? self::FLAG_FORCE_MOVE_LOCAL_ENTITY : 0;

			LE::writeUnsignedShort($out, $flags);
			if($this->xPos !== null){
				LE::writeFloat($out, $this->xPos);
			}
			if($this->yPos !== null){
				LE::writeFloat($out, $this->yPos);
			}
			if($this->zPos !== null){
				LE::writeFloat($out, $this->zPos);
			}
			if($this->pitch !== null){
				CommonTypes::putRotationByte($out, $this->pitch);
			}
			if($this->yaw !== null){
				CommonTypes::putRotationByte($out, $this->yaw);
			}
			if($this->headYaw !== null){
				CommonTypes::putRotationByte($out, $this->headYaw);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMoveActorDelta($this);
	}
}
