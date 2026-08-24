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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntryAction;

class SetScorePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCORE_PACKET;

	public const TYPE_CHANGE = 0;
	public const TYPE_REMOVE = 1;

	public int $type;
	/**
	 * @var ScorePacketEntry[]
	 * @phpstan-var list<ScorePacketEntry>
	 */
	private array $entries = [];

	/**
	 * @generate-create-func
	 * @param ScorePacketEntry[] $entries
	 * @phpstan-param list<ScorePacketEntry> $entries
	 */
	public static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return ScorePacketEntry[]
	 * @phpstan-return list<ScorePacketEntry>
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			$this->type = Byte::readUnsigned($in);
		}
		$this->entries = CommonTypes::readList($in, function(ByteBufferReader $in) use ($protocolId) : ScorePacketEntry{
			$entry = new ScorePacketEntry();

			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$action = ScorePacketEntryAction::fromOrdinal(VarInt::readUnsignedInt($in));
				$innerType = CommonTypes::getString($in);
				if($action !== ScorePacketEntryAction::fromPacket($innerType)){
					throw new PacketDecodeException("Expected inner type {$action->value} for score packet entry ordinal {$action->toOrdinal()}, got $innerType");
				}
				$entry->action = $action;

				//same for all types
				$entry->scoreboardId = VarInt::readSignedLong($in);

				if($action === ScorePacketEntryAction::REMOVE){
					if($protocolId >= ProtocolInfo::PROTOCOL_1_26_44){
						$entry->objectiveName = CommonTypes::readDoubleOptional($in, CommonTypes::getString(...));
					}else{
						$entry->objectiveName = CommonTypes::readOptional($in, CommonTypes::getString(...));
					}
				}elseif($action === ScorePacketEntryAction::CHANGE_PLAYER || $action === ScorePacketEntryAction::CHANGE_ENTITY){
					$entry->objectiveName = CommonTypes::getString($in);
					$entry->score = LE::readSignedInt($in);
					$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
				}elseif($action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
					$entry->objectiveName = CommonTypes::getString($in);
					$entry->score = LE::readSignedInt($in);
					$entry->customName = CommonTypes::getString($in);
				}else{ // this should never be the case
					throw new \LogicException("Unhandled decode for action: " . $action->name);
				}
			}else{
				$entry->scoreboardId = VarInt::readSignedLong($in);
				$entry->objectiveName = CommonTypes::getString($in);
				$entry->score = LE::readSignedInt($in);
				if($this->type === self::TYPE_REMOVE){
					$entry->action = ScorePacketEntryAction::REMOVE;
				}else{
					$type = Byte::readUnsigned($in);
					$entry->action = match($type){
						ScorePacketEntry::TYPE_PLAYER => ScorePacketEntryAction::CHANGE_PLAYER,
						ScorePacketEntry::TYPE_ENTITY => ScorePacketEntryAction::CHANGE_ENTITY,
						ScorePacketEntry::TYPE_FAKE_PLAYER => ScorePacketEntryAction::CHANGE_FAKE_PLAYER,
						default => throw new PacketDecodeException("Unknown entry type $type"),
					};
					if($entry->action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
						$entry->customName = CommonTypes::getString($in);
					}else{
						$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
					}
				}
			}
			return $entry;
		});
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $this->type);
		}

		CommonTypes::writeList($out, $this->entries, function(ByteBufferWriter $out, ScorePacketEntry $entry) use ($protocolId) : void{
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				VarInt::writeUnsignedInt($out, $entry->action->toOrdinal());
				CommonTypes::putString($out, $entry->action->value);

				//same for all types
				VarInt::writeSignedLong($out, $entry->scoreboardId);

				if($entry->action === ScorePacketEntryAction::REMOVE){
					if($protocolId >= ProtocolInfo::PROTOCOL_1_26_44){
						CommonTypes::writeDoubleOptional($out, $entry->objectiveName, CommonTypes::putString(...));
					}else{
						CommonTypes::writeOptional($out, $entry->objectiveName, CommonTypes::putString(...));
					}
				}elseif($entry->action === ScorePacketEntryAction::CHANGE_PLAYER || $entry->action === ScorePacketEntryAction::CHANGE_ENTITY){
					CommonTypes::putString($out, $entry->objectiveName);
					LE::writeSignedInt($out, $entry->score);
					CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				}elseif($entry->action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
					CommonTypes::putString($out, $entry->objectiveName);
					LE::writeSignedInt($out, $entry->score);
					CommonTypes::putString($out, $entry->customName ?? throw new \InvalidArgumentException("CustomName must be set for this entry type"));
				}else{ // this should never be the case
					throw new \LogicException("Unhandled encode for action: " . $entry->action->name);
				}
			}else{
				VarInt::writeSignedLong($out, $entry->scoreboardId);
				CommonTypes::putString($out, $entry->objectiveName ?? "");
				LE::writeSignedInt($out, $entry->score);
				if($this->type === self::TYPE_REMOVE){
					return;
				}

				Byte::writeUnsigned($out, match($entry->action){
					ScorePacketEntryAction::CHANGE_PLAYER => ScorePacketEntry::TYPE_PLAYER,
					ScorePacketEntryAction::CHANGE_ENTITY => ScorePacketEntry::TYPE_ENTITY,
					ScorePacketEntryAction::CHANGE_FAKE_PLAYER => ScorePacketEntry::TYPE_FAKE_PLAYER,
					ScorePacketEntryAction::REMOVE => throw new \InvalidArgumentException("REMOVE entries require the packet type to be TYPE_REMOVE before 1.26.40"),
				});
				if($entry->action === ScorePacketEntryAction::CHANGE_FAKE_PLAYER){
					CommonTypes::putString($out, $entry->customName ?? throw new \InvalidArgumentException("CustomName must be set for this entry type"));
				}else{
					CommonTypes::putActorUniqueId($out, $entry->actorUniqueId ?? throw new \InvalidArgumentException("ActorUniqueId must be set for this entry type"));
				}
			}
		});
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScore($this);
	}
}
