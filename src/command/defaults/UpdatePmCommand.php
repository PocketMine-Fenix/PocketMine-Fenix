<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\command\defaults;

use Phar;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\updater\SelfUpdateTask;
use pocketmine\utils\TextFormat;
use function basename;
use function dirname;
use const DIRECTORY_SEPARATOR;

class UpdatePmCommand extends VanillaCommand{
	private static bool $inProgress = false;

	public function __construct(){
		parent::__construct(
			"updatepm",
			"Updates PocketMine-Fenix to the latest GitHub release",
			"/updatepm",
			["upm", "selfupdate"]
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_UPDATEPM);
	}

	public static function setInProgress(bool $inProgress) : void{
		self::$inProgress = $inProgress;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}
		if(Phar::running(false) === ""){
			$sender->sendMessage(TextFormat::RED . "Self-update is only available when running from a PocketMine-Fenix.phar build.");
			return true;
		}
		if(self::$inProgress){
			$sender->sendMessage(TextFormat::YELLOW . "An update check is already in progress.");
			return true;
		}
		self::$inProgress = true;
		$sender->sendMessage("Checking for updates and downloading if available...");

		$currentPhar = Phar::running(false);
		$targetPath = dirname($currentPhar) . DIRECTORY_SEPARATOR . basename($currentPhar, ".phar") . ".phar.new";
		$sender->getServer()->getAsyncPool()->submitTask(new SelfUpdateTask($sender, $targetPath));

		return true;
	}
}
