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
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class PlayerAuthInputVehicleInfo{

	public function __construct(
		private ?Vector2 $vehicleRotation,
		private int $predictedVehicleActorUniqueId
	){}

	public function getVehicleRotation() : ?Vector2{ return $this->vehicleRotation; }

	public function getPredictedVehicleActorUniqueId() : int{ return $this->predictedVehicleActorUniqueId; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_70){
			$vehicleRotation = CommonTypes::getVector2($in);
		}
		$predictedVehicleActorUniqueId = CommonTypes::getActorUniqueId($in);

		return new self($vehicleRotation ?? null, $predictedVehicleActorUniqueId);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_70){
			CommonTypes::putVector2($out, $this->vehicleRotation ?? throw new \InvalidArgumentException("vehicleRotation must be set for 1.20.70+"));
		}
		CommonTypes::putActorUniqueId($out, $this->predictedVehicleActorUniqueId);
	}
}
