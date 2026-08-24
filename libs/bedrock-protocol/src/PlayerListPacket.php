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
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use function count;

class PlayerListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	public const TYPE_REMOVE = 0;
	public const TYPE_ADD = 1;

	/**
	 * @var int[]
	 * @phpstan-var array<self::TYPE_*, int>
	 */
	private const INNER_TYPES = [
		self::TYPE_ADD => 0,
		self::TYPE_REMOVE => 1,
	];

	public int $type;
	/** @var PlayerListEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param PlayerListEntry[] $entries
	 */
	public static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @param PlayerListEntry[] $entries
	 */
	public static function add(array $entries) : self{
		return self::create(self::TYPE_ADD, $entries);
	}

	/**
	 * @param PlayerListEntry[] $entries
	 */
	public static function remove(array $entries) : self{
		return self::create(self::TYPE_REMOVE, $entries);
	}

	/**
	 * @return PlayerListEntry[]
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = Byte::readUnsigned($in) === self::INNER_TYPES[self::TYPE_ADD] ? self::TYPE_ADD : self::TYPE_REMOVE;
		}

		$count = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$entry->type = VarInt::readUnsignedInt($in);
				$innerType = Byte::readUnsigned($in);
				$expectedInnerType = self::INNER_TYPES[$entry->type] ?? "unknown";
				if($innerType !== $expectedInnerType){
					throw new PacketDecodeException("Unexpected inner type $innerType for player list entry type $entry->type, expected $expectedInnerType");
				}
				$type = $entry->type;
			}else{
				$type = $this->type;
			}

			if($type === self::TYPE_ADD){
				$entry->uuid = CommonTypes::getUUID($in);
				$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
				$entry->username = CommonTypes::getString($in);
				$entry->xboxUserId = CommonTypes::getString($in);
				$entry->platformChatId = CommonTypes::getString($in);
				$entry->buildPlatform = LE::readSignedInt($in);
				$entry->skinData = CommonTypes::getSkin($in, $protocolId);
				$entry->isTeacher = CommonTypes::getBool($in);
				$entry->isHost = CommonTypes::getBool($in);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
					$entry->isSubClient = CommonTypes::getBool($in);
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
						$entry->color = CommonTypes::readColor($in);
					}
				}
			}elseif($type === self::TYPE_REMOVE){
				$entry->uuid = CommonTypes::getUUID($in);
			}else{
				throw new PacketDecodeException("Unknown player list entry type $type");
			}
			$this->entries[] = $entry;
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && $this->type === self::TYPE_ADD){
			foreach($this->entries as $entry){
				$entry->skinData->setVerified(CommonTypes::getBool($in));
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, self::INNER_TYPES[$this->type]);
		}

		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				VarInt::writeUnsignedInt($out, $entry->type);
				Byte::writeUnsigned($out, self::INNER_TYPES[$entry->type]);
			}elseif($entry->type !== $this->type){
				throw new \InvalidArgumentException("Add and remove entries cannot be mixed in the same packet before 1.26.40");
			}

			if($entry->type === self::TYPE_ADD){
				CommonTypes::putUUID($out, $entry->uuid);
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				CommonTypes::putString($out, $entry->username);
				CommonTypes::putString($out, $entry->xboxUserId);
				CommonTypes::putString($out, $entry->platformChatId);
				LE::writeSignedInt($out, $entry->buildPlatform);
				CommonTypes::putSkin($out, $protocolId, $entry->skinData);
				CommonTypes::putBool($out, $entry->isTeacher);
				CommonTypes::putBool($out, $entry->isHost);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
					CommonTypes::putBool($out, $entry->isSubClient);
					if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
						CommonTypes::writeColor($out, $entry->color ?? new Color(255, 255, 255));
					}
				}
			}else{
				CommonTypes::putUUID($out, $entry->uuid);
			}
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40 && $this->type === self::TYPE_ADD){
			foreach($this->entries as $entry){
				CommonTypes::putBool($out, $entry->skinData->isVerified());
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerList($this);
	}
}
