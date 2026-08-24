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
use function file_put_contents;
use function json_encode;
use function str_starts_with;
use function strlen;

/**
 * Silently pre-downloads the latest release PHAR so that a later /updatepm
 * (or server restart with an external swap) can apply it instantly.
 */
class AutoUpdateDownloadTask extends AsyncTask{
	private const MIN_PHAR_SIZE = 1_000_000;

	private const TLS_KEY_LOGGER = "logger";

	private string $error = "";

	public function __construct(
		\Logger $logger,
		private string $downloadUrl,
		private string $version,
		private string $targetPath
	){
		$this->storeLocal(self::TLS_KEY_LOGGER, $logger);
	}

	public function onRun() : void{
		$curlError = null;
		$response = Internet::getURL($this->downloadUrl, 600, [], $curlError);
		if($response === null){
			$this->error = $curlError !== null && $curlError !== "" ? $curlError : "Unknown network error";
			return;
		}
		$body = $response->getBody();
		if(strlen($body) < self::MIN_PHAR_SIZE || !str_starts_with($body, "<?php")){
			$this->error = "Downloaded file does not look like a valid PHAR";
			return;
		}
		if(file_put_contents($this->targetPath, $body) !== strlen($body)){
			$this->error = "Could not write PHAR to disk";
			return;
		}
		file_put_contents($this->targetPath . ".meta", json_encode(["version" => $this->version]));
	}

	public function onCompletion() : void{
		/** @var \Logger $logger */
		$logger = $this->fetchLocal(self::TLS_KEY_LOGGER);
		if($this->error !== ""){
			$logger->debug("Background update download failed: " . $this->error);
		}else{
			$logger->info("Update " . $this->version . " pre-downloaded - it will be applied by /updatepm or on next manual swap.");
		}
	}
}
