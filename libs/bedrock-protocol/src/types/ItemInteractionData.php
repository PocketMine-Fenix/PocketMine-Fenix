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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

final class ItemInteractionData{
	/**
	 * @param InventoryTransactionChangedSlotsHack[] $requestChangedSlots
	 * @phpstan-param list<InventoryTransactionChangedSlotsHack> $requestChangedSlots
	 */
	public function __construct(
		private int $requestId,
		private ?array $requestChangedSlots,
		private UseItemTransactionData $transactionData
	){}

	public function getRequestId() : int{
		return $this->requestId;
	}

	/**
	 * @return InventoryTransactionChangedSlotsHack[]|null
	 * @phpstan-return list<InventoryTransactionChangedSlotsHack>|null
	 */
	public function getRequestChangedSlots() : ?array{
		return $this->requestChangedSlots;
	}

	public function getTransactionData() : UseItemTransactionData{
		return $this->transactionData;
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$requestId = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$requestChangedSlots = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, InventoryTransactionChangedSlotsHack::read(...)));
		}elseif($requestId !== 0){
			$requestChangedSlots = CommonTypes::readList($in, InventoryTransactionChangedSlotsHack::read(...));
		}
		$transactionData = new UseItemTransactionData();
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::readDummyOptional($in);
			CommonTypes::readDummyOptional($in);
		}
		$transactionData->decodeAuthInput($in, $protocolId);
		return new ItemInteractionData($requestId, $requestChangedSlots ?? null, $transactionData);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeSignedInt($out, $this->requestId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeOptional($out, $this->requestChangedSlots, static fn($out, $list) => CommonTypes::writeList($out, $list, static fn($out, $v) => $v->write($out)));
			CommonTypes::writeDummyOptional($out);
			CommonTypes::writeDummyOptional($out);
		}elseif($this->requestId !== 0){
			CommonTypes::writeList($out, $this->requestChangedSlots ?? [], static fn($out, $v) => $v->write($out));
		}
		$this->transactionData->encodeAuthInput($out, $protocolId);
	}
}
