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

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;
use pocketmine\utils\VersionString;
use pocketmine\VersionInfo;
use function is_array;
use function is_string;
use function json_decode;
use function ltrim;
use function str_ends_with;
use function str_replace;
use function strtotime;

/**
 * Fetches the latest published release of this project from the GitHub Releases API.
 */
class UpdateCheckTask extends AsyncTask{
	private const TLS_KEY_UPDATER = "updater";

	private string $error = "Unknown error";

	public function __construct(UpdateChecker $updater){
		$this->storeLocal(self::TLS_KEY_UPDATER, $updater);
	}

	public static function apiUrlFromGithubUrl(string $githubUrl) : string{
		return str_replace("https://github.com/", "https://api.github.com/repos/", $githubUrl) . "/releases/latest";
	}

	/**
	 * Queries the GitHub Releases API for the latest published release.
	 *
	 * @param string $error set to a description of the problem on failure
	 * @phpstan-return array{version: string, channel: string, date: int, details_url: string, download_url: string}|null
	 */
	public static function queryLatestRelease(string &$error) : ?array{
		$error = "";
		$curlError = null;
		$response = Internet::getURL(
			self::apiUrlFromGithubUrl(VersionInfo::GITHUB_URL),
			10,
			[
				"Accept: application/vnd.github+json",
				"User-Agent: " . VersionInfo::NAME . "/" . VersionInfo::VERSION()->getFullVersion(true)
			],
			$curlError
		);
		if($response === null){
			$error = $curlError !== null && $curlError !== "" ? $curlError : "Unknown network error";
			return null;
		}
		$data = json_decode($response->getBody(), true);
		if(!is_array($data) || !isset($data["tag_name"]) || !is_string($data["tag_name"])){
			$error = "Invalid response data from GitHub Releases API";
			return null;
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

		$date = 0;
		if(isset($data["published_at"]) && is_string($data["published_at"])){
			$timestamp = strtotime($data["published_at"]);
			$date = $timestamp !== false ? $timestamp : 0;
		}

		return [
			"version" => ltrim($data["tag_name"], "v"),
			"channel" => $prerelease === true ? "beta" : "stable",
			"date" => $date,
			"details_url" => $detailsUrl,
			"download_url" => $downloadUrl,
		];
	}

	public function onRun() : void{
		$error = "";
		$release = self::queryLatestRelease($error);
		if($release === null){
			$this->error = $error;
			return;
		}

		try{
			new VersionString($release["version"]);
		}catch(\InvalidArgumentException){
			$this->error = "Invalid version string received from GitHub Releases";
			return;
		}

		$info = new UpdateInfo();
		$info->base_version = $release["version"];
		$info->is_dev = false;
		$info->channel = $release["channel"];
		$info->build = 0;
		$info->date = $release["date"];
		$info->details_url = $release["details_url"];
		$info->download_url = $release["download_url"];
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
