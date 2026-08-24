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
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\command\CommandOutputMessage;
use function array_search;
use function count;

class CommandOutputPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_OUTPUT_PACKET;

	public const TYPE_LAST = "lastoutput";
	public const TYPE_SILENT = "silent";
	public const TYPE_ALL = "alloutput";
	public const TYPE_DATA_SET = "dataset";

	private const TRANSLATION = [
		self::TYPE_LAST => 1,
		self::TYPE_SILENT => 2,
		self::TYPE_ALL => 3,
		self::TYPE_DATA_SET => 4,
	];

	public CommandOriginData $originData;
	public string $outputType;
	public int $successCount;
	/** @var CommandOutputMessage[] */
	public array $messages = [];
	public ?string $data;

	private function getOutputTypeFromId(int $typeId) : string{
		$outputType = array_search($typeId, self::TRANSLATION, true);
		if($outputType === false){
			throw new \InvalidArgumentException("Invalid output type id: $typeId");
		}
		return $outputType;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->originData = CommonTypes::getCommandOriginData($in, $protocolId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$this->outputType = CommonTypes::getString($in);
			$this->successCount = LE::readUnsignedInt($in);
		}else{
			$this->outputType = $this->getOutputTypeFromId(Byte::readUnsigned($in));
			$this->successCount = VarInt::readUnsignedInt($in);
		}

		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; ++$i){
			$this->messages[] = $this->getCommandMessage($in, $protocolId);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$this->data = CommonTypes::readOptional($in, CommonTypes::getString(...));
		}elseif($this->outputType === self::TYPE_DATA_SET){
			$this->data = CommonTypes::getString($in);
		}
	}

	/**
	 * @throws DataDecodeException
	 */
	protected function getCommandMessage(ByteBufferReader $in, int $protocolId) : CommandOutputMessage{
		$message = new CommandOutputMessage();

		if($protocolId <= ProtocolInfo::PROTOCOL_1_21_124){
			$message->isInternal = CommonTypes::getBool($in);
		}
		$message->messageId = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$message->isInternal = CommonTypes::getBool($in);
		}

		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; ++$i){
			$message->parameters[] = CommonTypes::getString($in);
		}

		return $message;
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putCommandOriginData($out, $this->originData, $protocolId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::putString($out, $this->outputType);
			LE::writeUnsignedInt($out, $this->successCount);
		}else{
			Byte::writeUnsigned($out, self::TRANSLATION[$this->outputType] ?? throw new \InvalidArgumentException("Invalid action type for protocol $protocolId: $this->outputType"));
			VarInt::writeUnsignedInt($out, $this->successCount);
		}

		VarInt::writeUnsignedInt($out, count($this->messages));
		foreach($this->messages as $message){
			$this->putCommandMessage($message, $out, $protocolId);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::writeOptional($out, $this->data, CommonTypes::putString(...));
		}elseif($this->outputType === self::TYPE_DATA_SET){
			CommonTypes::putString($out, $this->data ?? throw new \InvalidArgumentException("unknownString must be set for outputType dataset"));
		}
	}

	protected function putCommandMessage(CommandOutputMessage $message, ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId <= ProtocolInfo::PROTOCOL_1_21_124){
			CommonTypes::putBool($out, $message->isInternal);
		}
		CommonTypes::putString($out, $message->messageId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::putBool($out, $message->isInternal);
		}

		VarInt::writeUnsignedInt($out, count($message->parameters));
		foreach($message->parameters as $parameter){
			CommonTypes::putString($out, $parameter);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCommandOutput($this);
	}
}
