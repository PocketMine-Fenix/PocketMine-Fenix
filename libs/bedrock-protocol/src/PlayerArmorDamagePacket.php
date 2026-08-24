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
use pocketmine\network\mcpe\protocol\types\ArmorSlot;
use pocketmine\network\mcpe\protocol\types\ArmorSlotAndDamagePair;
use function count;

class PlayerArmorDamagePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_ARMOR_DAMAGE_PACKET;

	private const FLAG_HEAD = 0;
	private const FLAG_CHEST = 1;
	private const FLAG_LEGS = 2;
	private const FLAG_FEET = 3;
	private const FLAG_BODY = 4;

	/**
	 * @var ArmorSlotAndDamagePair[]
	 * @phpstan-var list<ArmorSlotAndDamagePair>
	 */
	private array $armorSlotAndDamagePairs = [];

	/**
	 * @generate-create-func
	 * @param ArmorSlotAndDamagePair[] $armorSlotAndDamagePairs
	 * @phpstan-param list<ArmorSlotAndDamagePair> $armorSlotAndDamagePairs
	 */
	public static function create(array $armorSlotAndDamagePairs) : self{
		$result = new self;
		$result->armorSlotAndDamagePairs = $armorSlotAndDamagePairs;
		return $result;
	}

	/**
	 * @return ArmorSlotAndDamagePair[]
	 * @phpstan-return list<ArmorSlotAndDamagePair>
	 */
	public function getArmorSlotAndDamagePairs() : array{
		return $this->armorSlotAndDamagePairs;
	}

	private function maybeReadDamage(int $flags, int $flag, ByteBufferReader $in) : ?int{
		if(($flags & (1 << $flag)) !== 0){
			return VarInt::readSignedInt($in);
		}
		return null;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_111){
			$length = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $length; ++$i){
				$this->armorSlotAndDamagePairs[] = ArmorSlotAndDamagePair::read($in);
			}
		}else{
			$flags = Byte::readUnsigned($in);

			if(($headSlotDamage = $this->maybeReadDamage($flags, self::FLAG_HEAD, $in)) !== null){
				$this->armorSlotAndDamagePairs[] = new ArmorSlotAndDamagePair(ArmorSlot::HEAD, $headSlotDamage);
			}

			if(($chestSlotDamage = $this->maybeReadDamage($flags, self::FLAG_CHEST, $in)) !== null){
				$this->armorSlotAndDamagePairs[] = new ArmorSlotAndDamagePair(ArmorSlot::TORSO, $chestSlotDamage);
			}

			if(($legsSlotDamage = $this->maybeReadDamage($flags, self::FLAG_LEGS, $in)) !== null){
				$this->armorSlotAndDamagePairs[] = new ArmorSlotAndDamagePair(ArmorSlot::LEGS, $legsSlotDamage);
			}

			if(($feetSlotDamage = $this->maybeReadDamage($flags, self::FLAG_FEET, $in)) !== null){
				$this->armorSlotAndDamagePairs[] = new ArmorSlotAndDamagePair(ArmorSlot::FEET, $feetSlotDamage);
			}

			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				if(($bodySlotDamage = $this->maybeReadDamage($flags, self::FLAG_BODY, $in)) !== null){
					$this->armorSlotAndDamagePairs[] = new ArmorSlotAndDamagePair(ArmorSlot::BODY, $bodySlotDamage);
				}
			}
		}
	}

	private function composeFlag(?int $field, int $flag) : int{
		return $field !== null ? (1 << $flag) : 0;
	}

	private function maybeWriteDamage(?int $field, ByteBufferWriter $out) : void{
		if($field !== null){
			VarInt::writeSignedInt($out, $field);
		}
	}

	private function getDamageBySlot(ArmorSlot $slot) : ?int{
		foreach($this->armorSlotAndDamagePairs as $pair){
			if($pair->getSlot() === $slot){
				return $pair->getDamage();
			}
		}

		return null;
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_111){
			VarInt::writeUnsignedInt($out, count($this->armorSlotAndDamagePairs));
			foreach($this->armorSlotAndDamagePairs as $pair){
				$pair->write($out);
			}
		}else{
			$headSlotDamage = $this->getDamageBySlot(ArmorSlot::HEAD);
			$chestSlotDamage = $this->getDamageBySlot(ArmorSlot::TORSO);
			$legsSlotDamage = $this->getDamageBySlot(ArmorSlot::LEGS);
			$feetSlotDamage = $this->getDamageBySlot(ArmorSlot::FEET);
			$bodySlotDamage = $this->getDamageBySlot(ArmorSlot::BODY);

			Byte::writeUnsigned(
				$out,
				$this->composeFlag($headSlotDamage, self::FLAG_HEAD) |
				$this->composeFlag($chestSlotDamage, self::FLAG_CHEST) |
				$this->composeFlag($legsSlotDamage, self::FLAG_LEGS) |
				$this->composeFlag($feetSlotDamage, self::FLAG_FEET) |
				($protocolId >= ProtocolInfo::PROTOCOL_1_21_20 ? $this->composeFlag($bodySlotDamage, self::FLAG_BODY) : 0)
			);

			$this->maybeWriteDamage($headSlotDamage, $out);
			$this->maybeWriteDamage($chestSlotDamage, $out);
			$this->maybeWriteDamage($legsSlotDamage, $out);
			$this->maybeWriteDamage($feetSlotDamage, $out);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
				$this->maybeWriteDamage($bodySlotDamage, $out);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerArmorDamage($this);
	}
}
