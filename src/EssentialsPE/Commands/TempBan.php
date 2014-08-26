<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\permission\BanEntry;
use pocketmine\utils\TextFormat;

class TempBan extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "tempban", "Temporary bans the specified player", "/tempban <player> <time ...> [reason ...]");
        $this->setPermission("essentials.tempban");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) < 2){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }
        $player = $this->getAPI()->getPlayer(array_shift($args));
        if($player === false){
            $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
            return false;
        }
        $seconds = 0;
        while(preg_match_all('#^(\d+(\.\d+)?)(y|mo|w|d|h|m|s)$#', $shifted = array_shift($args), $match, PREG_SET_ORDER)){
            $match = $match[0]; // it won't get two matches anyways, or we will just ignore it (like 2w3s will only be parsed as 2 "w"eeks)
            if(!is_numeric($match[1])){
                break;
            }
            $unit = 1;
            switch(strtolower($match[3])){
                case "m": $unit = 60; break;
                case "h": $unit = 60 * 60; break;
                case "d": $unit = 60 * 60 * 24; break;
                case "w": $unit = 60 * 60 * 24 * 7; break;
                case "mo": $unit = 60 * 60 * 24 * ((int) date("t")); break; // never knew I were to use "t" ;)
                case "y": $unit = 60 * 60 * 24 * (date("L") === "1" ? 366:365); break;
            }
            $amplifier = floatval($match[1]);
            $seconds += $amplifier * $unit;
        }
        if(is_string($shifted)) {
            array_unshift($args, $shifted);
        }
        $reason = implode(" ", $args);
        $date = new \DateTime;
        $date->setTimestamp($expiryTimestamp = time() + $seconds);
        $ban = new BanEntry($player->getName());
        $ban->setExpires($date);
        $ban->setReason($reason);
        $this->getPlugin()->getServer()->getNameBans()->add($ban);
        $format = "M jS y H:i:s";
        $player->close("Banned until " . date($format, $expiryTimestamp));
        return true;
    }
}
