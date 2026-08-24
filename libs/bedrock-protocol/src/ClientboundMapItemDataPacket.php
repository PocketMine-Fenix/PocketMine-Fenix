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
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;
use function count;

class ClientboundMapItemDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public const BITFLAG_TEXTURE_UPDATE = 0x02;
	public const BITFLAG_DECORATION_UPDATE = 0x04;
	public const BITFLAG_MAP_CREATION = 0x08;

	public int $mapId;
	public int $dimensionId = DimensionIds::OVERWORLD;
	public bool $isLocked = false;
	public BlockPosition $origin;

	/**
	 * @var int[]
	 * @phpstan-var list<int>
	 */
	public ?array $parentMapIds = null;
	public ?int $scale = null;

	/**
	 * @var MapTrackedObject[]
	 * @phpstan-var list<MapTrackedObject>
	 */
	public ?array $trackedEntities = null;
	/**
	 * @var MapDecoration[]
	 * @phpstan-var list<MapDecoration>
	 */
	public ?array $decorations = null;

	public ?int $xOffset = null;
	public ?int $yOffset = null;
	public ?MapImage $colors = null;

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->mapId = CommonTypes::getActorUniqueId($in);
		$type = $protocolId < ProtocolInfo::PROTOCOL_1_26_40 ? VarInt::readUnsignedInt($in) : null;
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);
		$this->origin = CommonTypes::getBlockPosition($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);

		$signedY = $protocolId >= ProtocolInfo::PROTOCOL_1_26_10;
		$readTrackedObject = static function(ByteBufferReader $in) use ($signedY) : MapTrackedObject{
			$object = new MapTrackedObject();
			$object->type = LE::readUnsignedInt($in);
			if($object->type === MapTrackedObject::TYPE_BLOCK){
				$object->blockPosition = CommonTypes::getBlockPosition($in, $signedY);
			}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
				$object->actorUniqueId = CommonTypes::getActorUniqueId($in);
			}else{
				throw new PacketDecodeException("Unknown map object type $object->type");
			}
			return $object;
		};
		$readDecoration = static function(ByteBufferReader $in) use ($protocolId) : MapDecoration{
			$icon = Byte::readUnsigned($in);
			$rotation = Byte::readUnsigned($in);
			$xOffset = Byte::readUnsigned($in);
			$yOffset = Byte::readUnsigned($in);
			$label = CommonTypes::getString($in);
			$color = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ?
				CommonTypes::readColor($in) :
				Color::fromRGBA(Binary::flipIntEndianness(VarInt::readUnsignedInt($in)));
			return new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
		};

		if($type !== null){
			if(($type & self::BITFLAG_MAP_CREATION) !== 0){
				$this->parentMapIds = CommonTypes::readList($in, CommonTypes::getActorUniqueId(...));
			}
			if(($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){
				$this->scale = Byte::readUnsigned($in);
			}
			if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
				$this->trackedEntities = CommonTypes::readList($in, $readTrackedObject);
				$this->decorations = CommonTypes::readList($in, $readDecoration);
			}
			if(($type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
				$width = VarInt::readSignedInt($in);
				$height = VarInt::readSignedInt($in);
				$this->xOffset = VarInt::readSignedInt($in);
				$this->yOffset = VarInt::readSignedInt($in);

				$count = VarInt::readUnsignedInt($in);
				if($count !== $width * $height){
					throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
				}

				$this->colors = MapImage::decode($in, $protocolId, $height, $width);
			}
			return;
		}

		$this->parentMapIds = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, CommonTypes::getActorUniqueId(...)));

		$this->scale = CommonTypes::readOptional($in, Byte::readUnsigned(...));

		$this->trackedEntities = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, $readTrackedObject));

		$this->decorations = CommonTypes::readOptional($in, static fn($in) => CommonTypes::readList($in, $readDecoration));

		$width = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
		$height = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
		$this->xOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
		$this->yOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...));

		$this->colors = CommonTypes::readOptional($in, static function($in) use ($protocolId, $width, $height){
			if($width === null || $height === null){
				//ensure the packet can't get into an inconsistent state for re-encoding
				throw new PacketDecodeException("Expected both width and height to be present if colors are present");
			}
			$count = VarInt::readUnsignedInt($in);
			if($count !== $width * $height){
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			return MapImage::decode($in, $protocolId, $height, $width);
		});
		if($this->colors === null && ($this->xOffset !== null || $this->yOffset !== null)){
			//ensure the packet can't get into an inconsistent state for re-encoding
			throw new PacketDecodeException("Expected both xOffset and yOffset to be null if colors are null");
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorUniqueId($out, $this->mapId);

		$type = null;
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			$type = 0;
			if($this->parentMapIds !== null && count($this->parentMapIds) > 0){
				$type |= self::BITFLAG_MAP_CREATION;
			}
			if($this->decorations !== null && count($this->decorations) > 0){
				$type |= self::BITFLAG_DECORATION_UPDATE;
			}
			if($this->colors !== null){
				$type |= self::BITFLAG_TEXTURE_UPDATE;
			}

			VarInt::writeUnsignedInt($out, $type);
		}
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);
		CommonTypes::putBlockPosition($out, $this->origin, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);

		$signedY = $protocolId >= ProtocolInfo::PROTOCOL_1_26_10;
		$writeTrackedObject = static function(ByteBufferWriter $out, MapTrackedObject $object) use ($signedY) : void{
			LE::writeUnsignedInt($out, $object->type);
			if($object->type === MapTrackedObject::TYPE_BLOCK){
				CommonTypes::putBlockPosition($out, $object->blockPosition, $signedY);
			}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
				CommonTypes::putActorUniqueId($out, $object->actorUniqueId);
			}else{
				throw new \InvalidArgumentException("Unknown map object type $object->type");
			}
		};
		$writeDecoration = static function(ByteBufferWriter $out, MapDecoration $decoration) use ($protocolId) : void{
			Byte::writeUnsigned($out, $decoration->getIcon());
			Byte::writeUnsigned($out, $decoration->getRotation());
			Byte::writeUnsigned($out, $decoration->getXOffset());
			Byte::writeUnsigned($out, $decoration->getYOffset());
			CommonTypes::putString($out, $decoration->getLabel());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				CommonTypes::writeColor($out, $decoration->getColor());
			}else{
				VarInt::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		};

		if($type !== null){
			if(($type & self::BITFLAG_MAP_CREATION) !== 0){
				CommonTypes::writeList($out, $this->parentMapIds ?? [], CommonTypes::putActorUniqueId(...));
			}
			if(($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){
				Byte::writeUnsigned($out, $this->scale ?? 0);
			}
			if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
				CommonTypes::writeList($out, $this->trackedEntities ?? [], $writeTrackedObject);
				CommonTypes::writeList($out, $this->decorations ?? [], $writeDecoration);
			}
			if(($type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
				$colors = $this->colors ?? throw new \AssertionError("colors must be set if BITFLAG_TEXTURE_UPDATE is set");
				VarInt::writeSignedInt($out, $colors->getWidth());
				VarInt::writeSignedInt($out, $colors->getHeight());
				VarInt::writeSignedInt($out, $this->xOffset ?? 0);
				VarInt::writeSignedInt($out, $this->yOffset ?? 0);

				VarInt::writeUnsignedInt($out, $colors->getWidth() * $colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang
				$colors->encode($out, $protocolId);
			}
			return;
		}

		CommonTypes::writeOptional($out, $this->parentMapIds, static fn($out, $v) => CommonTypes::writeList($out, $v, CommonTypes::putActorUniqueId(...)));

		CommonTypes::writeOptional($out, $this->scale, Byte::writeUnsigned(...));

		CommonTypes::writeOptional($out, $this->trackedEntities, static fn($out, $v) => CommonTypes::writeList($out, $v, $writeTrackedObject));

		CommonTypes::writeOptional($out, $this->decorations, static fn($out, $v) => CommonTypes::writeList($out, $v, $writeDecoration));

		//TODO: this is icky but it's better than requiring callers to specify height and width separately from colors
		$colors = $this->colors;
		CommonTypes::writeOptional($out, $colors?->getWidth(), VarInt::writeSignedInt(...));
		CommonTypes::writeOptional($out, $colors?->getHeight(), VarInt::writeSignedInt(...));
		CommonTypes::writeOptional($out, $this->xOffset, VarInt::writeSignedInt(...));
		CommonTypes::writeOptional($out, $this->yOffset, VarInt::writeSignedInt(...));

		CommonTypes::writeOptional($out, $colors, static function($out, $colors) use ($protocolId) : void{
			VarInt::writeUnsignedInt($out, $colors->getWidth() * $colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang
			$colors->encode($out, $protocolId);
		});
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundMapItemData($this);
	}
}
