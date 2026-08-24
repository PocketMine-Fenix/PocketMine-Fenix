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

use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\updater\UpdateInfo;
use pocketmine\utils\TextFormat;
use pocketmine\utils\VersionString;
use pocketmine\VersionInfo;
use function explode;
use function preg_replace;
use function trim;

class WhatsNewCommand extends VanillaCommand{

	public function __construct(){
		parent::__construct(
			"whatsnew",
			"Shows the release notes of the latest PocketMine-Fenix release",
			"/whatsnew",
			["changelog"]
		);
		$this->setPermission(DefaultPermissionNames::COMMAND_WHATSNEW);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}
		$info = $sender->getServer()->getUpdater()->getLatestKnown();
		if($info === null){
			$sender->getServer()->getUpdater()->doCheck(); //kick off an async check for next time
			$sender->sendMessage(TextFormat::YELLOW . "No release information available yet. Try again in a few seconds.");
			return true;
		}

		$sender->sendMessage(TextFormat::GOLD . "--- " . VersionInfo::NAME . " " . TextFormat::AQUA . $info->base_version . TextFormat::GOLD . " ---");
		if($this->serverRunningLatest($info)){
			$sender->sendMessage(TextFormat::GREEN . "You are running this version.");
		}else{
			$sender->sendMessage(TextFormat::YELLOW . "This version is newer than yours - use /updatepm to update.");
		}

		$body = trim((string) preg_replace("/!\[[^\]]*\]\([^)]*\)/", "", $info->body)); //strip images
		$body = (string) preg_replace("/\[([^\]]*)\]\([^)]*\)/", "$1", $body); //links -> text
		if($body === ""){
			$body = "(no release notes provided)";
		}
		$lines = 0;
		foreach(explode("\n", $body, 100) as $line){
			$trimmed = trim($line);
			if($trimmed === ""){
				continue;
			}
			$sender->sendMessage(TextFormat::GRAY . $trimmed);
			if(++$lines >= 25){
				$sender->sendMessage(TextFormat::GRAY . "... full notes: " . TextFormat::AQUA . $info->details_url);
				break;
			}
		}
		if($lines < 25){
			$sender->sendMessage(TextFormat::GRAY . "Details: " . TextFormat::AQUA . $info->details_url);
		}

		return true;
	}

	private function serverRunningLatest(UpdateInfo $info) : bool{
		try{
			//compare() > 0 means the target ($info) is newer than the running build
			return VersionInfo::VERSION()->compare(new VersionString($info->base_version)) <= 0;
		}catch(\InvalidArgumentException){
			return false;
		}
	}
}
