<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___/     |_|  |_|_|
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

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;
use pocketmine\VersionInfo;
use function is_array;
use function json_decode;
use function str_ends_with;
use function str_replace;
use function strtotime;

/**
 * Fetches the latest published release of this project from the GitHub Releases API.
 */
class UpdateCheckTask extends AsyncTask{
	private const TLS_KEY_UPDATER = "updater";

	private string $error = "Unknown error";

	public function __construct(
		UpdateChecker $updater,
		private string $apiUrl
	){
		$this->storeLocal(self::TLS_KEY_UPDATER, $updater);
	}

	public static function apiUrlFromGithubUrl(string $githubUrl) : string{
		return str_replace("https://github.com/", "https://api.github.com/repos/", $githubUrl) . "/releases/latest";
	}

	public function onRun() : void{
		$error = "";
		$response = Internet::getURL($this->apiUrl, 4,
			[
				"Accept: application/vnd.github+json",
				"User-Agent: " . VersionInfo::NAME . "/" . VersionInfo::VERSION()->getFullVersion(true)
			],
			$error
		);
		$this->error = $error;

		if($response === null){
			return;
		}
		$data = json_decode($response->getBody(), true);
		if(!is_array($data) || !isset($data["tag_name"]) || !is_string($data["tag_name"])){
			$this->error = "Invalid response data from GitHub Releases API";
			return;
		}

		$detailsUrl = VersionInfo::GITHUB_URL . "/releases";
		if(isset($data["html_url"]) && is_string($data["html_url"])){
			$detailsUrl = $data["html_url"];
		}

		$downloadUrl = "";
		$assets = $data["assets"] ?? null;
		if(is_array($assets)){
			foreach($assets as $asset){
				if(
					is_array($asset) &&
					isset($asset["name"], $asset["browser_download_url"]) &&
					is_string($asset["name"]) && is_string($asset["browser_download_url"]) &&
					str_ends_with($asset["name"], ".phar")
				){
					$downloadUrl = $asset["browser_download_url"];
					break;
				}
			}
		}

		$prerelease = isset($data["prerelease"]) ? $data["prerelease"] : false;

		$info = new UpdateInfo();
		$info->base_version = ltrim($data["tag_name"], "v");
		$info->is_dev = false;
		$info->channel = $prerelease === true ? "beta" : "stable";

		$publishedAt = $data["published_at"] ?? null;
		if(is_string($publishedAt)){
			$timestamp = strtotime($publishedAt);
			$info->date = $timestamp !== false ? $timestamp : 0;
		}else{
			$info->date = 0;
		}
		$info->build = 0;
		$info->details_url = $detailsUrl;
		$info->download_url = $downloadUrl;
		$info->source_url = VersionInfo::GITHUB_URL;

		$this->setResult($info);
	}

	public function onCompletion() : void{
		/** @var UpdateChecker $updater */
		$updater = $this->fetchLocal(self::TLS_KEY_UPDATER);
		if($this->hasResult()){
			/** @var UpdateInfo $response */
			$response = $this->getResult();
			$updater->checkUpdateCallback($response);
		}else{
			$updater->checkUpdateError($this->error);
		}
	}
}
