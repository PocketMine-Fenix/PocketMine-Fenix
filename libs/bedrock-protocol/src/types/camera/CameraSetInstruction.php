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
use pmmp\encoding\LE;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;
use function is_infinite;
use function is_nan;

final class CameraSetInstruction{

	public function __construct(
		private int $preset,
		private ?CameraSetInstructionEase $ease,
		private ?Vector3 $cameraPosition,
		private ?CameraSetInstructionRotation $rotation,
		private ?Vector3 $facingPosition,
		private ?Vector2 $viewOffset,
		private ?Vector3 $entityOffset,
		private ?bool $default,
		private bool $ignoreStartingValuesComponent
	){}

	public function getPreset() : int{ return $this->preset; }

	public function getEase() : ?CameraSetInstructionEase{ return $this->ease; }

	public function getCameraPosition() : ?Vector3{ return $this->cameraPosition; }

	public function getRotation() : ?CameraSetInstructionRotation{ return $this->rotation; }

	public function getFacingPosition() : ?Vector3{ return $this->facingPosition; }

	public function getViewOffset() : ?Vector2{ return $this->viewOffset; }

	public function getEntityOffset() : ?Vector3{ return $this->entityOffset; }

	public function getDefault() : ?bool{ return $this->default; }

	public function isIgnoringStartingValuesComponent() : bool{ return $this->ignoreStartingValuesComponent; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$preset = LE::readUnsignedInt($in);
		$ease = CommonTypes::readOptional($in, CameraSetInstructionEase::read(...));
		$cameraPosition = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
		$rotation = CommonTypes::readOptional($in, CameraSetInstructionRotation::read(...));
		$facingPosition = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$viewOffset = CommonTypes::readOptional($in, CommonTypes::getVector2(...));
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			$entityOffset = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
		}
		$default = CommonTypes::readOptional($in, CommonTypes::getBool(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
			$ignoreStartingValuesComponent = CommonTypes::getBool($in);
		}

		return new self(
			$preset,
			$ease,
			$cameraPosition,
			$rotation,
			$facingPosition,
			$viewOffset ?? null,
			$entityOffset ?? null,
			$default,
			$ignoreStartingValuesComponent ?? false,
		);
	}

	public static function fromNBT(CompoundTag $nbt) : self{
		$preset = $nbt->getInt("preset");

		$easeTag = $nbt->getCompoundTag("ease");
		$ease = $easeTag !== null ? CameraSetInstructionEase::fromNBT($easeTag) : null;

		// no clue why there's a "pos" listTag inside a compoundTag called "pos"
		$cameraPositionTag = $nbt->getCompoundTag("pos");
		$cameraPosition = $cameraPositionTag !== null ? self::parseVec3($cameraPositionTag, "pos") : null;

		$rotationTag = $nbt->getCompoundTag("rot");
		$rotation = $rotationTag !== null ? CameraSetInstructionRotation::fromNBT($rotationTag) : null;

		$facingTag = $nbt->getCompoundTag("facing");
		$facingPosition = $facingTag !== null ? self::parseVec3($facingTag, "facing") : null;

		$default = $nbt->getByte("default", 0) !== 0;

		return new self(
			$preset,
			$ease,
			$cameraPosition,
			$rotation,
			$facingPosition,
			null,
			null,
			$default,
			false,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeUnsignedInt($out, $this->preset);
		CommonTypes::writeOptional($out, $this->ease, fn(ByteBufferWriter $out, CameraSetInstructionEase $v) => $v->write($out));
		CommonTypes::writeOptional($out, $this->cameraPosition, CommonTypes::putVector3(...));
		CommonTypes::writeOptional($out, $this->rotation, fn(ByteBufferWriter $out, CameraSetInstructionRotation $v) => $v->write($out));
		CommonTypes::writeOptional($out, $this->facingPosition, CommonTypes::putVector3(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			CommonTypes::writeOptional($out, $this->viewOffset, CommonTypes::putVector2(...));
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_40){
			CommonTypes::writeOptional($out, $this->entityOffset, CommonTypes::putVector3(...));
		}
		CommonTypes::writeOptional($out, $this->default, CommonTypes::putBool(...));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
			CommonTypes::putBool($out, $this->ignoreStartingValuesComponent);
		}
	}

	public function toNBT() : CompoundTag{
		$nbt = CompoundTag::create();
		$nbt->setInt("preset", $this->preset);

		if($this->ease !== null){
			$nbt->setTag("ease", $this->ease->toNBT());
		}

		if($this->cameraPosition !== null){
			$nbt->setTag("pos", CompoundTag::create()
				->setTag("pos", new ListTag([
					new FloatTag($this->cameraPosition->x),
					new FloatTag($this->cameraPosition->y),
					new FloatTag($this->cameraPosition->z),
				]))
			);
		}

		if($this->rotation !== null){
			$nbt->setTag("rot", $this->rotation->toNBT());
		}

		if($this->facingPosition !== null){
			$nbt->setTag("facing", CompoundTag::create()
				->setTag("facing", new ListTag([
					new FloatTag($this->facingPosition->x),
					new FloatTag($this->facingPosition->y),
					new FloatTag($this->facingPosition->z),
				]))
			);
		}

		if($this->default !== null){
			$nbt->setByte("default", $this->default ? 1 : 0);
		}

		return $nbt;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function parseVec3(CompoundTag $nbt, string $tagName) : ?Vector3{
		$pos = $nbt->getTag($tagName);
		if($pos === null){
			return null;
		}
		if(!($pos instanceof ListTag) || $pos->getTagType() !== NBT::TAG_Float){
			throw new \InvalidArgumentException("'$tagName' should be a List<Double> or List<Float>");
		}
		/** @var DoubleTag[]|FloatTag[] $values */
		$values = $pos->getValue();
		if(count($values) !== 3){
			throw new \InvalidArgumentException("Expected exactly 3 entries in '$tagName' tag");
		}

		$x = $values[0]->getValue();
		$y = $values[1]->getValue();
		$z = $values[2]->getValue();

		self::validateFloat($tagName, "x", $x);
		self::validateFloat($tagName, "y", $y);
		self::validateFloat($tagName, "z", $z);

		return new Vector3($x, $y, $z);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private static function validateFloat(string $tagName, string $component, float $value) : void{
		if(is_infinite($value)){
			throw new \InvalidArgumentException("$component component of '$tagName' contains invalid infinite value");
		}
		if(is_nan($value)){
			throw new \InvalidArgumentException("$component component of '$tagName' contains invalid NaN value");
		}
	}
}
