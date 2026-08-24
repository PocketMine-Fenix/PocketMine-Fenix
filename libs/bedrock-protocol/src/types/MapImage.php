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

use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\Binary;
use function count;

final class MapImage{
	//these limits are enforced in the protocol in 1.20.0
	public const MAX_HEIGHT = 128;
	public const MAX_WIDTH = 128;

	/** < ProtocolInfo::PROTOCOL_1_26_40 */
	private const PIXEL_FORMAT_LEGACY = 0;
	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	private const PIXEL_FORMAT_NEW = 1;

	private int $width;
	private int $height;
	/**
	 * @var Color[][]
	 * @phpstan-var list<list<Color>>
	 */
	private array $pixels;

	/**
	 * @var string[]
	 * @phpstan-var array<self::PIXEL_FORMAT_*, string>
	 */
	private array $encodedPixelCache = [];

	/**
	 * @param Color[][] $pixels
	 * @phpstan-param list<list<Color>> $pixels
	 */
	public function __construct(array $pixels){
		$rowLength = null;
		foreach($pixels as $row){
			if($rowLength === null){
				$rowLength = count($row);
			}elseif(count($row) !== $rowLength){
				throw new \InvalidArgumentException("All rows must have the same number of pixels");
			}
		}
		if($rowLength === null){
			throw new \InvalidArgumentException("No pixels provided");
		}
		if($rowLength > self::MAX_WIDTH){
			throw new \InvalidArgumentException("Image width must be at most " . self::MAX_WIDTH . " pixels wide");
		}
		if(count($pixels) > self::MAX_HEIGHT){
			throw new \InvalidArgumentException("Image height must be at most " . self::MAX_HEIGHT . " pixels tall");
		}
		$this->height = count($pixels);
		$this->width = $rowLength;
		$this->pixels = $pixels;
	}

	public function getWidth() : int{ return $this->width; }

	public function getHeight() : int{ return $this->height; }

	/**
	 * @return Color[][]
	 * @phpstan-return list<list<Color>>
	 */
	public function getPixels() : array{ return $this->pixels; }

	public function encode(ByteBufferWriter $out, int $protocolId) : void{
		$format = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? self::PIXEL_FORMAT_NEW : self::PIXEL_FORMAT_LEGACY;
		if(!isset($this->encodedPixelCache[$format])){
			$serializer = new ByteBufferWriter();
			for($y = 0; $y < $this->height; ++$y){
				for($x = 0; $x < $this->width; ++$x){
					if($format === self::PIXEL_FORMAT_NEW){
						BE::writeSignedInt($serializer, $this->pixels[$y][$x]->toRGBA());
					}else{
						VarInt::writeUnsignedInt($serializer, Binary::flipIntEndianness($this->pixels[$y][$x]->toRGBA()));
					}
				}
			}
			$this->encodedPixelCache[$format] = $serializer->getData();
		}

		$out->writeByteArray($this->encodedPixelCache[$format]);
	}

	/**
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function decode(ByteBufferReader $in, int $protocolId, int $height, int $width) : self{
		if($width > self::MAX_WIDTH){
			throw new PacketDecodeException("Image width must be at most " . self::MAX_WIDTH . " pixels wide");
		}
		if($height > self::MAX_HEIGHT){
			throw new PacketDecodeException("Image height must be at most " . self::MAX_HEIGHT . " pixels tall");
		}
		$pixels = [];

		for($y = 0; $y < $height; ++$y){
			$row = [];
			for($x = 0; $x < $width; ++$x){
				$row[] = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ?
					Color::fromRGBA(BE::readSignedInt($in)) :
					Color::fromRGBA(Binary::flipIntEndianness(VarInt::readUnsignedInt($in)));
			}
			$pixels[] = $row;
		}

		return new self($pixels);
	}
}
