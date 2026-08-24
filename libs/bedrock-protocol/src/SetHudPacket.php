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
use pocketmine\network\mcpe\protocol\types\hud\HudElement;
use pocketmine\network\mcpe\protocol\types\hud\HudVisibility;
use function count;

class SetHudPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_HUD_PACKET;

	/** @var HudElement[] */
	private array $hudElements = [];
	private HudVisibility $visibility;

	/**
	 * @generate-create-func
	 * @param HudElement[] $hudElements
	 */
	public static function create(array $hudElements, HudVisibility $visibility) : self{
		$result = new self;
		$result->hudElements = $hudElements;
		$result->visibility = $visibility;
		return $result;
	}

	/** @return HudElement[] */
	public function getHudElements() : array{ return $this->hudElements; }

	public function getVisibility() : HudVisibility{ return $this->visibility; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->hudElements = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$this->hudElements[] = HudElement::fromPacket($protocolId >= ProtocolInfo::PROTOCOL_1_21_70 ? VarInt::readSignedInt($in) : Byte::readUnsigned($in));
		}
		$this->visibility = HudVisibility::fromPacket($protocolId >= ProtocolInfo::PROTOCOL_1_21_70 ? VarInt::readSignedInt($in) : Byte::readUnsigned($in));
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, count($this->hudElements));
		foreach($this->hudElements as $element){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_70){
				VarInt::writeSignedInt($out, $element->value);
			}else{
				Byte::writeUnsigned($out, $element->value);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_70){
			VarInt::writeSignedInt($out, $this->visibility->value);
		}else{
			Byte::writeUnsigned($out, $this->visibility->value);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetHud($this);
	}
}
