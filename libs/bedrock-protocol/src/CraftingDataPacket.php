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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MultiRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\SmithingTransformRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\SmithingTrimRecipe;
use function count;

class CraftingDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4;
	public const ENTRY_USER_DATA_SHAPELESS = 5;
	public const ENTRY_SHAPELESS_CHEMISTRY = 6;
	public const ENTRY_SHAPED_CHEMISTRY = 7;
	public const ENTRY_SMITHING_TRANSFORM = 8;
	public const ENTRY_SMITHING_TRIM = 9;

	/**
	 * @var ShapedRecipe[]
	 * @phpstan-var list<ShapedRecipe>
	 */
	public array $shapedRecipes = [];
	/**
	 * @var ShapelessRecipe[]
	 * @phpstan-var list<ShapelessRecipe>
	 */
	public array $shapelessRecipes = [];
	/**
	 * @var MultiRecipe[]
	 * @phpstan-var list<MultiRecipe>
	 */
	public array $multiRecipes = [];
	/**
	 * @var ShapelessRecipe[]
	 * @phpstan-var list<ShapelessRecipe>
	 */
	public array $userDataShapelessRecipes = [];
	/**
	 * @var ShapelessRecipe[]
	 * @phpstan-var list<ShapelessRecipe>
	 */
	public array $shapelessChemistryRecipes = [];
	/**
	 * @var ShapedRecipe[]
	 * @phpstan-var list<ShapedRecipe>
	 */
	public array $shapedChemistryRecipes = [];
	/**
	 * @var SmithingTransformRecipe[]
	 * @phpstan-var list<SmithingTransformRecipe>
	 */
	public array $smithingTransformRecipes = [];
	/**
	 * @var SmithingTrimRecipe[]
	 * @phpstan-var list<SmithingTrimRecipe>
	 */
	public array $smithingTrimRecipes = [];
	/**
	 * @var FurnaceRecipe[]
	 * @phpstan-var list<FurnaceRecipe>
	 */
	public array $furnaceRecipes = [];

	/**
	 * @var PotionTypeRecipe[]
	 * @phpstan-var list<PotionTypeRecipe>
	 */
	public array $potionTypeRecipes = [];
	/**
	 * @var PotionContainerChangeRecipe[]
	 * @phpstan-var list<PotionContainerChangeRecipe>
	 */
	public array $potionContainerRecipes = [];
	/**
	 * @var MaterialReducerRecipe[]
	 * @phpstan-var list<MaterialReducerRecipe>
	 */
	public array $materialReducerRecipes = [];
	public bool $cleanRecipes = false;

	/**
	 * @generate-create-func
	 * @param ShapedRecipe[]                $shapedRecipes
	 * @param ShapelessRecipe[]             $shapelessRecipes
	 * @param MultiRecipe[]                 $multiRecipes
	 * @param ShapelessRecipe[]             $userDataShapelessRecipes
	 * @param ShapelessRecipe[]             $shapelessChemistryRecipes
	 * @param ShapedRecipe[]                $shapedChemistryRecipes
	 * @param SmithingTransformRecipe[]     $smithingTransformRecipes
	 * @param SmithingTrimRecipe[]          $smithingTrimRecipes
	 * @param FurnaceRecipe[]               $furnaceRecipes
	 * @param PotionTypeRecipe[]            $potionTypeRecipes
	 * @param PotionContainerChangeRecipe[] $potionContainerRecipes
	 * @param MaterialReducerRecipe[]       $materialReducerRecipes
	 * @phpstan-param list<ShapedRecipe>                $shapedRecipes
	 * @phpstan-param list<ShapelessRecipe>             $shapelessRecipes
	 * @phpstan-param list<MultiRecipe>                 $multiRecipes
	 * @phpstan-param list<ShapelessRecipe>             $userDataShapelessRecipes
	 * @phpstan-param list<ShapelessRecipe>             $shapelessChemistryRecipes
	 * @phpstan-param list<ShapedRecipe>                $shapedChemistryRecipes
	 * @phpstan-param list<SmithingTransformRecipe>     $smithingTransformRecipes
	 * @phpstan-param list<SmithingTrimRecipe>          $smithingTrimRecipes
	 * @phpstan-param list<FurnaceRecipe>               $furnaceRecipes
	 * @phpstan-param list<PotionTypeRecipe>            $potionTypeRecipes
	 * @phpstan-param list<PotionContainerChangeRecipe> $potionContainerRecipes
	 * @phpstan-param list<MaterialReducerRecipe>       $materialReducerRecipes
	 */
	public static function create(
		array $shapedRecipes,
		array $shapelessRecipes,
		array $multiRecipes,
		array $userDataShapelessRecipes,
		array $shapelessChemistryRecipes,
		array $shapedChemistryRecipes,
		array $smithingTransformRecipes,
		array $smithingTrimRecipes,
		array $furnaceRecipes,
		array $potionTypeRecipes,
		array $potionContainerRecipes,
		array $materialReducerRecipes,
		bool $cleanRecipes,
	) : self{
		$result = new self;
		$result->shapedRecipes = $shapedRecipes;
		$result->shapelessRecipes = $shapelessRecipes;
		$result->multiRecipes = $multiRecipes;
		$result->userDataShapelessRecipes = $userDataShapelessRecipes;
		$result->shapelessChemistryRecipes = $shapelessChemistryRecipes;
		$result->shapedChemistryRecipes = $shapedChemistryRecipes;
		$result->smithingTransformRecipes = $smithingTransformRecipes;
		$result->smithingTrimRecipes = $smithingTrimRecipes;
		$result->furnaceRecipes = $furnaceRecipes;
		$result->potionTypeRecipes = $potionTypeRecipes;
		$result->potionContainerRecipes = $potionContainerRecipes;
		$result->materialReducerRecipes = $materialReducerRecipes;
		$result->cleanRecipes = $cleanRecipes;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->shapedRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => ShapedRecipe::decode($in, $protocolId));
			$this->shapelessRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => ShapelessRecipe::decode($in, $protocolId));
			$this->multiRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => MultiRecipe::decode($in, $protocolId));
			$this->userDataShapelessRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => ShapelessRecipe::decode($in, $protocolId));
			$this->shapelessChemistryRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => ShapelessRecipe::decode($in, $protocolId));
			$this->shapedChemistryRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => ShapedRecipe::decode($in, $protocolId));
			$this->smithingTransformRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => SmithingTransformRecipe::decode($in, $protocolId));
			$this->smithingTrimRecipes = CommonTypes::readList($in, fn(ByteBufferReader $in) => SmithingTrimRecipe::decode($in, $protocolId));
		}else{
			$recipeCount = VarInt::readUnsignedInt($in);
			$previousType = "none";
			for($i = 0; $i < $recipeCount; ++$i){
				$recipeType = VarInt::readSignedInt($in);

				match($recipeType){
					self::ENTRY_SHAPELESS => $this->shapelessRecipes[] = ShapelessRecipe::decode($in, $protocolId),
					self::ENTRY_USER_DATA_SHAPELESS => $this->userDataShapelessRecipes[] = ShapelessRecipe::decode($in, $protocolId),
					self::ENTRY_SHAPELESS_CHEMISTRY => $this->shapelessChemistryRecipes[] = ShapelessRecipe::decode($in, $protocolId),
					self::ENTRY_SHAPED => $this->shapedRecipes[] = ShapedRecipe::decode($in, $protocolId),
					self::ENTRY_SHAPED_CHEMISTRY => $this->shapedChemistryRecipes[] = ShapedRecipe::decode($in, $protocolId),
					self::ENTRY_FURNACE, self::ENTRY_FURNACE_DATA => $this->furnaceRecipes[] = FurnaceRecipe::decode($recipeType, $in, $protocolId),
					self::ENTRY_MULTI => $this->multiRecipes[] = MultiRecipe::decode($in, $protocolId),
					self::ENTRY_SMITHING_TRANSFORM => $this->smithingTransformRecipes[] = SmithingTransformRecipe::decode($in, $protocolId),
					self::ENTRY_SMITHING_TRIM => $this->smithingTrimRecipes[] = SmithingTrimRecipe::decode($in, $protocolId),
					default => throw new PacketDecodeException("Unhandled recipe type $recipeType (previous was $previousType)"),
				};
				$previousType = $recipeType;
			}
		}
		$this->potionTypeRecipes = CommonTypes::readList($in, PotionTypeRecipe::decode(...));
		$this->potionContainerRecipes = CommonTypes::readList($in, PotionContainerChangeRecipe::decode(...));
		$this->materialReducerRecipes = CommonTypes::readList($in, MaterialReducerRecipe::decode(...));
		$this->cleanRecipes = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::writeList($out, $this->shapedRecipes, fn(ByteBufferWriter $out, ShapedRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->shapelessRecipes, fn(ByteBufferWriter $out, ShapelessRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->multiRecipes, fn(ByteBufferWriter $out, MultiRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->userDataShapelessRecipes, fn(ByteBufferWriter $out, ShapelessRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->shapelessChemistryRecipes, fn(ByteBufferWriter $out, ShapelessRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->shapedChemistryRecipes, fn(ByteBufferWriter $out, ShapedRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->smithingTransformRecipes, fn(ByteBufferWriter $out, SmithingTransformRecipe $recipe) => $recipe->encode($out, $protocolId));
			CommonTypes::writeList($out, $this->smithingTrimRecipes, fn(ByteBufferWriter $out, SmithingTrimRecipe $recipe) => $recipe->encode($out, $protocolId));
		}else{
			//:(
			VarInt::writeUnsignedInt($out, count($this->shapelessRecipes) + count($this->shapedRecipes) + count($this->furnaceRecipes) + count($this->multiRecipes) +
				count($this->userDataShapelessRecipes) + count($this->shapelessChemistryRecipes) + count($this->shapedChemistryRecipes) +
				count($this->smithingTransformRecipes) + count($this->smithingTrimRecipes));

			foreach([
				self::ENTRY_SHAPELESS => $this->shapelessRecipes,
				self::ENTRY_SHAPED => $this->shapedRecipes,
				self::ENTRY_MULTI => $this->multiRecipes,
				self::ENTRY_USER_DATA_SHAPELESS => $this->userDataShapelessRecipes,
				self::ENTRY_SHAPELESS_CHEMISTRY => $this->shapelessChemistryRecipes,
				self::ENTRY_SHAPED_CHEMISTRY => $this->shapedChemistryRecipes,
				self::ENTRY_SMITHING_TRANSFORM => $this->smithingTransformRecipes,
				self::ENTRY_SMITHING_TRIM => $this->smithingTrimRecipes,
			] as $recipeType => $recipes){
				foreach($recipes as $recipe){
					VarInt::writeSignedInt($out, $recipeType);
					$recipe->encode($out, $protocolId);
				}
			}
			foreach($this->furnaceRecipes as $recipe){
				VarInt::writeSignedInt($out, $recipe->getTypeId());
				$recipe->encode($out, $protocolId);
			}
		}
		CommonTypes::writeList($out, $this->potionTypeRecipes, fn(ByteBufferWriter $out, PotionTypeRecipe $recipe) => $recipe->encode($out));
		CommonTypes::writeList($out, $this->potionContainerRecipes, fn(ByteBufferWriter $out, PotionContainerChangeRecipe $recipe) => $recipe->encode($out));
		CommonTypes::writeList($out, $this->materialReducerRecipes, fn(ByteBufferWriter $out, MaterialReducerRecipe $recipe) => $recipe->encode($out));
		CommonTypes::putBool($out, $this->cleanRecipes);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCraftingData($this);
	}
}
