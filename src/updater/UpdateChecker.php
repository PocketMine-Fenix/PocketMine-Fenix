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
use pocketmine\event\server\UpdateNotifyEvent;
use pocketmine\Server;
use pocketmine\utils\VersionString;
use pocketmine\VersionInfo;
use pocketmine\YmlServerProperties;
use function basename;
use function date;
use function dirname;
use function is_file;
use function strtolower;
use function time;
use function ucfirst;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;

/**
 * Checks for updates by querying this project's GitHub Releases.
 */
class UpdateChecker{

	protected Server $server;
	protected ?UpdateInfo $updateInfo = null;
	private ?UpdateInfo $latestKnown = null;
	private int $nextCheckTime = PHP_INT_MAX;
	private \Logger $logger;

	public function __construct(Server $server){
		$this->server = $server;
		$this->logger = new \PrefixedLogger($server->getLogger(), "Update Checker");

		if($server->getConfigGroup()->getPropertyBool(YmlServerProperties::AUTO_UPDATER_ENABLED, true)){
			$this->doCheck();
		}
	}

	/**
	 * Called every tick from Server::tick(). Schedules periodic re-checks
	 * according to auto-updater.check-interval-minutes (0 disables them).
	 */
	public function tick(int $currentTick) : void{
		if(!$this->server->getConfigGroup()->getPropertyBool(YmlServerProperties::AUTO_UPDATER_ENABLED, true)){
			return;
		}
		$intervalMinutes = $this->server->getConfigGroup()->getPropertyInt(YmlServerProperties::AUTO_UPDATER_CHECK_INTERVAL_MINUTES, 60);
		if($intervalMinutes <= 0){
			return;
		}
		if($this->nextCheckTime === PHP_INT_MAX){
			//first tick: schedule the next check relative to the startup check that already ran
			$this->nextCheckTime = time() + $intervalMinutes * 60;
			return;
		}
		if(time() >= $this->nextCheckTime){
			$this->nextCheckTime = time() + $intervalMinutes * 60;
			$this->doCheck();
		}
	}

	/**
	 * Returns information about the latest known release, regardless of whether
	 * it is newer than the running version. Null until the first check completes.
	 */
	public function getLatestKnown() : ?UpdateInfo{
		return $this->latestKnown;
	}

	public function checkUpdateError(string $error) : void{
		$this->logger->debug("Async update check failed due to \"$error\"");
	}

	/**
	 * Callback used at the end of the update checking task
	 */
	public function checkUpdateCallback(UpdateInfo $updateInfo) : void{
		$this->latestKnown = $updateInfo;
		$this->checkUpdate($updateInfo);
		if($this->hasUpdate()){
			(new UpdateNotifyEvent($this))->call();
			if($this->server->getConfigGroup()->getPropertyBool(YmlServerProperties::AUTO_UPDATER_ON_UPDATE_WARN_CONSOLE, true)){
				$this->showConsoleUpdate();
			}
			if($this->server->getConfigGroup()->getPropertyBool(YmlServerProperties::AUTO_UPDATER_AUTO_DOWNLOAD, false)){
				$this->preDownloadLatest($updateInfo);
			}
		}else{
			if(!VersionInfo::IS_DEVELOPMENT_BUILD && $this->getChannel() !== "stable"){
				$this->showChannelSuggestionStable();
			}elseif(VersionInfo::IS_DEVELOPMENT_BUILD && $this->getChannel() === "stable"){
				$this->showChannelSuggestionBeta();
			}
		}
	}

	/**
	 * Returns whether there is an update available.
	 */
	public function hasUpdate() : bool{
		return $this->updateInfo !== null;
	}

	/**
	 * Posts a warning to the console to tell the user there is an update available
	 */
	public function showConsoleUpdate() : void{
		if($this->updateInfo === null){
			return;
		}
		$newVersion = new VersionString($this->updateInfo->base_version, $this->updateInfo->is_dev, $this->updateInfo->build);
		$messages = [
			"Your version of " . $this->server->getName() . " is out of date. Version " . $newVersion->getFullVersion(true) . " was released on " . date("D M j h:i:s Y", $this->updateInfo->date)
		];

		$messages[] = "Details: " . $this->updateInfo->details_url;
		if($this->updateInfo->download_url !== ""){
			$messages[] = "Download: " . $this->updateInfo->download_url;
		}

		$this->printConsoleMessage($messages, \LogLevel::WARNING);
	}

	protected function showChannelSuggestionStable() : void{
		$this->printConsoleMessage([
			"You're running a Stable build, but you're receiving update notifications for " . ucfirst($this->getChannel()) . " builds.",
			"To get notified about new Stable builds only, change 'preferred-channel' in your pocketmine.yml to 'stable'."
		]);
	}

	protected function showChannelSuggestionBeta() : void{
		$this->printConsoleMessage([
			"You're running a Beta build, but you're receiving update notifications for Stable builds.",
			"To get notified about new Beta or Development builds, change 'preferred-channel' in your pocketmine.yml to 'beta' or 'development'."
		]);
	}

	/**
	 * @param string[] $lines
	 */
	protected function printConsoleMessage(array $lines, string $logLevel = \LogLevel::INFO) : void{
		foreach($lines as $line){
			$this->logger->log($logLevel, $line);
		}
	}

	/**
	 * Returns the last retrieved update data.
	 */
	public function getUpdateInfo() : ?UpdateInfo{
		return $this->updateInfo;
	}

	/**
	 * Schedules an AsyncTask to check for an update.
	 */
	public function doCheck() : void{
		$this->logger->debug("Checking for updates from GitHub Releases");
		$this->server->getAsyncPool()->submitTask(new UpdateCheckTask($this));
	}

	/**
	 * Checks the update information against the current server version to decide if there's an update
	 */
	protected function checkUpdate(UpdateInfo $updateInfo) : void{
		$currentVersion = VersionInfo::VERSION();
		try{
			$newVersion = new VersionString($updateInfo->base_version, $updateInfo->is_dev, $updateInfo->build);
		}catch(\InvalidArgumentException $e){
			//Invalid version returned from API, assume there's no update
			$this->logger->debug("Assuming no update because \"" . $e->getMessage() . "\"");
			return;
		}

		if(strtolower($this->getChannel()) === "stable" && strtolower($updateInfo->channel) !== "stable"){
			//prerelease available, but this server prefers stable channel
			$this->logger->debug("A prerelease ($newVersion) is available but preferred-channel is stable");
			return;
		}

		//NOTE: compare() returns > 0 when $newVersion (the target) is newer than the current version
		if($currentVersion->compare($newVersion) > 0){
			$this->updateInfo = $updateInfo;
		}else{
			$this->logger->debug("Latest release (" . $newVersion->getFullVersion() . ") is not newer than the current version, not showing notification");
		}
	}

	/**
	 * Returns the channel used for update checking (stable, beta, dev)
	 */
	public function getChannel() : string{
		return strtolower($this->server->getConfigGroup()->getPropertyString(YmlServerProperties::AUTO_UPDATER_PREFERRED_CHANNEL, "stable"));
	}

	private function preDownloadLatest(UpdateInfo $updateInfo) : void{
		if(Phar::running(false) === "" || $updateInfo->download_url === ""){
			return;
		}
		$currentPhar = Phar::running(false);
		$targetPath = dirname($currentPhar) . DIRECTORY_SEPARATOR . basename($currentPhar, ".phar") . ".phar.new";
		if(is_file($targetPath)){
			return; //already pre-downloaded (or a leftover - /updatepm will validate it)
		}
		$this->logger->info("Pre-downloading update " . $updateInfo->base_version . " in background...");
		$this->server->getAsyncPool()->submitTask(new AutoUpdateDownloadTask(
			$this->logger,
			$updateInfo->download_url,
			$updateInfo->base_version,
			$targetPath
		));
	}
}
