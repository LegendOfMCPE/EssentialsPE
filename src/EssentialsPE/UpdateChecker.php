<?php

namespace EssentialsPE;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class UpdateChecker{
	const CHANNEL_RELEASE = 1;
	const CHANNEL_PRERELEASE = 2;
	/** @var Loader */
	private $loader;
	/** @var int */
	private $channel;
	/** @var string */
	private $dataFolder, $pluginFile, $source, $version, $newestReleasePath = null;
	/** @var string[] */
	private $actions;
	public function __construct(Loader $loader, $array, $version){
		$this->loader = $loader;
		$this->dataFolder = $loader->getDataFolder() . "versions/";
		@mkdir($this->dataFolder, 0777, true);
		$this->pluginFile = $loader->getFile();
		$this->channel = $this->channelStringToInt($array["channel"]);
		$this->actions = $array["actions"];
		$this->source = $array["source"];
		$this->version = $version;
	}
	private function channelStringToInt($string){
		switch($string){
			case "release":
				return self::CHANNEL_RELEASE;
			case "prerelease":
				return self::CHANNEL_PRERELEASE;
		}
		return -1;
	}
	public function check(array &$array){
		switch($this->channel){
			case self::CHANNEL_RELEASE:
			case self::CHANNEL_PRERELEASE:
				$array = array_merge($array, $this->checkRelease());
		}
	}
	/**
	 * This method <b>is blocking</b>. Call from an AsyncTask.
	 */
	private function checkRelease(){
		$url = "https://api.github.com/repos/LegendOfMCPE/EssentialsPE/releases";
		$result = Utils::getURL($url);
		if(is_string($result)){
			$releases = json_decode($result, true);
			foreach($releases as $release){
				$isPre = $release["prerelease"];
				if($this->channel === self::CHANNEL_RELEASE and $isPre){
					continue;
				}
				return $this->act($release["name"], $release["assets"][0], $release["body"], $release["tag_name"]);
			}
			return function(CommandSender $recipient) use($url){
				$recipient->sendMessage("$url returns no available releases.");
			};
		}
		else{
			return function(CommandSender $recipient) use($url){
				$recipient->sendMessage("Fatal: no response from $url");
			};
		}
	}
	private function act($name, $dl, $body, $tagName){
		$runs = [];
		foreach($this->actions as $action){
			switch($action){
				case "notify-console":
					/** @noinspection PhpUnusedParameterInspection @noinspection PhpDocSignatureInspection */
					$runs[] = function(CommandSender $r, Loader $loader) use($name, $dl, $body){
						$loader->getLogger()->alert("A new release is available!");
						$loader->getLogger()->info("$name: $body");
						$loader->getLogger()->info("Available at $dl");
					};
					break;
				case "notify-ops":
					/** @noinspection PhpUnusedParameterInspection @noinspection PhpDocSignatureInspection */
					$runs[] = function(CommandSender $r, Loader $loader) use($name, $dl, $body){
						foreach($loader->getServer()->getPluginManager()->getPermissionSubscriptions("essentials.essentials.notifyupdate") as $permissible){
							if($permissible instanceof Player){
								$permissible->sendMessage(TextFormat::YELLOW . "A new release of EssentialsPE is available!");
								$permissible->sendMessage(TextFormat::AQUA . $name . TextFormat::RESET . ": " . TextFormat::LIGHT_PURPLE . $body);
								$permissible->sendMessage("Downlaod avilable at $dl");
							}
						}
					};
					break;
				case "download":
					file_put_contents($this->newestReleasePath = $this->dataFolder . $tagName . ".phar", Utils::getURL($dl)); # any errors like file locks on this line?
					break;
				case "install&stop":
					if(!@is_file($this->newestReleasePath)){
						$runs[] = function(CommandSender $r){
							$r->getServer()->getLogger()->error("UpdateChecker: install&stop must be run after download!");
							$r->sendMessage("Fatal: malconfigured config file");
						};
						break;
					}
					unlink($this->pluginFile);
					copy($this->newestReleasePath, $this->pluginFile);
					/** @noinspection PhpUnusedParameterInspection @noinspection PhpDocSignatureInspection */
					$runs[] = function(CommandSender $r){
						foreach($r->getServer()->getOnlinePlayers() as $p){
							$p->kick("EssentialsPE update");
						}
						$r->getServer()->shutdown();
					};
					break;
			}
		}
		return $runs;
	}
}
