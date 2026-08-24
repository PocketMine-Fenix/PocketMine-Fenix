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

namespace pocketmine\network\mcpe\protocol\types\camera;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class CameraAimAssistCategories{

	/**
	 * @param CameraAimAssistCategory[] $categories
	 */
	public function __construct(
		private string $identifier,
		private array $categories
	){}

	public function getIdentifier() : string{ return $this->identifier; }

	/**
	 * @return CameraAimAssistCategory[]
	 */
	public function getCategories() : array{ return $this->categories; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$identifier = CommonTypes::getString($in);

		$categories = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$categories[] = CameraAimAssistCategory::read($in, $protocolId);
		}

		return new self(
			$identifier,
			$categories
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->identifier);
		VarInt::writeUnsignedInt($out, count($this->categories));
		foreach($this->categories as $category){
			$category->write($out, $protocolId);
		}
	}
}
