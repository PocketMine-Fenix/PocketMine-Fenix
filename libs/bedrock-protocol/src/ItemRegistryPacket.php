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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use function count;

class ItemRegistryPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::ITEM_REGISTRY_PACKET;

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	private array $entries;

	/**
	 * @generate-create-func
	 * @param ItemTypeEntry[] $entries
	 * @phpstan-param list<ItemTypeEntry> $entries
	 */
	public static function create(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return ItemTypeEntry[]
	 * @phpstan-return list<ItemTypeEntry>
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->entries = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$stringId = CommonTypes::getString($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
				$numericId = LE::readSignedShort($in);
				$isComponentBased = CommonTypes::getBool($in);
				$version = VarInt::readSignedInt($in);
			}
			$nbt = CommonTypes::getNbtCompoundRoot($in);
			$this->entries[] = new ItemTypeEntry($stringId, $numericId ?? -1, $isComponentBased ?? false, $version ?? -1, new CacheableNbt($nbt));
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			CommonTypes::putString($out, $entry->getStringId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
				LE::writeSignedShort($out, $entry->getNumericId());
				CommonTypes::putBool($out, $entry->isComponentBased());
				VarInt::writeSignedInt($out, $entry->getVersion());
			}
			$out->writeByteArray($entry->getComponentNbt()->getEncodedNbt());
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleItemRegistry($this);
	}
}
