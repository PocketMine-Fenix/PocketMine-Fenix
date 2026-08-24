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

namespace pocketmine\updater;

use Phar;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\UpdatePmCommand;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;
use pocketmine\utils\VersionString;
use pocketmine\VersionInfo;
use function basename;
use function clearstatcache;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function rename;
use function str_starts_with;
use function strlen;
use function unlink;

/**
 * Downloads the latest release PHAR and swaps it into place, so that a restart
 * completes the update. Triggered by the /updatepm command.
 */
class SelfUpdateTask extends AsyncTask{
	private const TLS_KEY_SENDER = "sender";

	private const MIN_PHAR_SIZE = 1_000_000;

	private string $error = "";
	private bool $alreadyLatest = false;
	private string $newVersion = "";

	public function __construct(
		CommandSender $sender,
		private string $targetPath
	){
		$this->storeLocal(self::TLS_KEY_SENDER, $sender);
	}

	public function onRun() : void{
		$error = "";
		$release = UpdateCheckTask::queryLatestRelease($error);
		if($release === null){
			$this->error = $error;
			return;
		}

		try{
			$newVersion = new VersionString($release["version"]);
		}catch(\InvalidArgumentException){
			$this->error = "Invalid version string received from GitHub Releases";
			return;
		}

		//NOTE: compare() returns > 0 when $newVersion is NEWER than the running version
		if(VersionInfo::VERSION()->compare($newVersion) <= 0){
			$this->alreadyLatest = true;
			return;
		}
		$this->newVersion = $release["version"];

		if($release["download_url"] === ""){
			$this->error = "The latest release does not contain a .phar asset";
			return;
		}

		//Fast path: the file may already have been pre-downloaded by the background
		//updater (auto-updater.auto-download) - reuse it if it matches this release.
		if($this->tryReusePreDownloaded($release["version"])){
			return;
		}

		$curlError = null;
		$response = Internet::getURL($release["download_url"], 600, [], $curlError);
		if($response === null){
			$this->error = "Download failed: " . (is_string($curlError) && $curlError !== "" ? $curlError : "unknown error");
			return;
		}
		$body = $response->getBody();
		if(strlen($body) < self::MIN_PHAR_SIZE || !str_starts_with($body, "<?php")){
			$this->error = "Downloaded file does not look like a valid PocketMine-Fenix PHAR";
			return;
		}
		if(file_put_contents($this->targetPath, $body) !== strlen($body)){
			$this->error = "Could not write downloaded PHAR to disk (check folder permissions)";
		}
	}

	private function tryReusePreDownloaded(string $expectedVersion) : bool{
		$metaFile = $this->targetPath . ".meta";
		if(!is_file($this->targetPath) || !is_file($metaFile)){
			return false;
		}
		$raw = file_get_contents($metaFile);
		if($raw === false){
			return false;
		}
		$meta = json_decode($raw, true);
		if(!is_array($meta) || !isset($meta["version"]) || !is_string($meta["version"]) || $meta["version"] !== $expectedVersion){
			return false;
		}
		clearstatcache(true, $this->targetPath);
		return filesize($this->targetPath) >= self::MIN_PHAR_SIZE;
	}

	public function onCompletion() : void{
		/** @var CommandSender $sender */
		$sender = $this->fetchLocal(self::TLS_KEY_SENDER);
		UpdatePmCommand::setInProgress(false);

		if($this->error !== ""){
			$sender->sendMessage(TextFormat::RED . "Update failed: " . $this->error);
			return;
		}
		if($this->alreadyLatest){
			$sender->sendMessage(TextFormat::GREEN . "You are already running the latest version of " . VersionInfo::NAME . ".");
			return;
		}

		$current = Phar::running(false);
		$oldFile = $current . ".old";
		$swapped = false;
		if(@rename($current, $oldFile)){
			if(@rename($this->targetPath, $current)){
				$swapped = true;
				@unlink($oldFile); //may fail while the old PHAR is still in use - harmless leftover
			}else{
				@rename($oldFile, $current); //roll back
			}
		}

		if($swapped){
			$sender->sendMessage(TextFormat::GREEN . "Successfully updated to " . VersionInfo::NAME . " " . $this->newVersion . ".");
			$sender->sendMessage(TextFormat::YELLOW . "Restart the server to apply the update.");
		}else{
			$sender->sendMessage(TextFormat::RED . "The new version was downloaded but could not replace the running PHAR automatically.");
			$sender->sendMessage(TextFormat::YELLOW . "Delete the current PHAR manually and rename " . TextFormat::AQUA . basename($this->targetPath) . TextFormat::YELLOW . " in its place, then start the server.");
			$sender->sendMessage(TextFormat::GRAY . "(Downloaded file location: " . dirname($this->targetPath) . ")");
		}
	}
}
