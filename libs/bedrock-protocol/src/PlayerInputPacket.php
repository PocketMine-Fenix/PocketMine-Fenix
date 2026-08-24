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

class PlayerInputPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_INPUT_PACKET;

	public float $motionX;
	public float $motionY;
	public bool $jumping;
	public bool $sneaking;

	/**
	 * @generate-create-func
	 */
	public static function create(float $motionX, float $motionY, bool $jumping, bool $sneaking) : self{
		$result = new self;
		$result->motionX = $motionX;
		$result->motionY = $motionY;
		$result->jumping = $jumping;
		$result->sneaking = $sneaking;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->motionX = LE::readFloat($in);
		$this->motionY = LE::readFloat($in);
		$this->jumping = CommonTypes::getBool($in);
		$this->sneaking = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeFloat($out, $this->motionX);
		LE::writeFloat($out, $this->motionY);
		CommonTypes::putBool($out, $this->jumping);
		CommonTypes::putBool($out, $this->sneaking);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerInput($this);
	}
}
