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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackresponse;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Spec name: ItemStackResponseInfo
 */
final class ItemStackResponse{

	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;
	//TODO: there are a ton more possible result types but we don't need them yet and they are wayyyyyy too many for me
	//to waste my time on right now...

	/**
	 * @param ItemStackResponseContainerInfo[]|null $containerInfos
	 * @phpstan-param list<ItemStackResponseContainerInfo>|null $containerInfos
	 */
	public function __construct(
		private int $result,
		private int $requestId,
		private ?array $containerInfos
	){
		if($this->result !== self::RESULT_OK && $this->containerInfos !== null){
			throw new \InvalidArgumentException("Container infos must be null if rejecting the request");
		}
	}

	public function getResult() : int{ return $this->result; }

	public function getRequestId() : int{ return $this->requestId; }

	/**
	 * @return ItemStackResponseContainerInfo[]|null
	 * @phpstan-return list<ItemStackResponseContainerInfo>|null
	 */
	public function getContainerInfos() : ?array{ return $this->containerInfos; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$result = Byte::readUnsigned($in);
		$requestId = CommonTypes::readItemStackRequestId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$containerInfos = CommonTypes::readDoubleOptional($in, fn(ByteBufferReader $in) => CommonTypes::readList($in, fn(ByteBufferReader $in) => ItemStackResponseContainerInfo::read($in, $protocolId)));
		}else{
			$containerInfos = $result === self::RESULT_OK ?
				CommonTypes::readList($in, fn(ByteBufferReader $in) => ItemStackResponseContainerInfo::read($in, $protocolId)) :
				null;
		}
		return new self($result, $requestId, $containerInfos);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->result);
		CommonTypes::writeItemStackRequestId($out, $this->requestId);
		$writeContainerInfo = fn(ByteBufferWriter $out, ItemStackResponseContainerInfo $v) => $v->write($out, $protocolId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeDoubleOptional($out, $this->containerInfos, fn(ByteBufferWriter $out, array $list) => CommonTypes::writeList($out, $list, $writeContainerInfo));
		}elseif($this->result === self::RESULT_OK){
			CommonTypes::writeList($out, $this->containerInfos ?? [], $writeContainerInfo);
		}
	}
}
