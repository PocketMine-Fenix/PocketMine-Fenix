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
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Useless leftover from a 1.8 refactor, does nothing
 */
class LevelSoundEventPacketV1 extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_SOUND_EVENT_PACKET_V1;

	public int $sound;
	public Vector3 $position;
	public int $extraData = 0;
	public int $entityType = 1;
	public bool $isBabyMob = false; //...
	public bool $disableRelativeVolume = false;

	/**
	 * @generate-create-func
	 */
	public static function create(int $sound, Vector3 $position, int $extraData, int $entityType, bool $isBabyMob, bool $disableRelativeVolume) : self{
		$result = new self;
		$result->sound = $sound;
		$result->position = $position;
		$result->extraData = $extraData;
		$result->entityType = $entityType;
		$result->isBabyMob = $isBabyMob;
		$result->disableRelativeVolume = $disableRelativeVolume;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->sound = Byte::readUnsigned($in);
		$this->position = CommonTypes::getVector3($in);
		$this->extraData = VarInt::readSignedInt($in);
		$this->entityType = VarInt::readSignedInt($in);
		$this->isBabyMob = CommonTypes::getBool($in);
		$this->disableRelativeVolume = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->sound);
		CommonTypes::putVector3($out, $this->position);
		VarInt::writeSignedInt($out, $this->extraData);
		VarInt::writeSignedInt($out, $this->entityType);
		CommonTypes::putBool($out, $this->isBabyMob);
		CommonTypes::putBool($out, $this->disableRelativeVolume);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleLevelSoundEventPacketV1($this);
	}
}
