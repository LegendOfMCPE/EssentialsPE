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
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return false;
        }
        $player = $this->getPlugin()->getPlayer($name = array_shift($args));
        /**
         * s = Seconds (with leading zeros)
         * i = Minutes
         * h = Hour (12 hours format with leading zeros)
         * j = Day number (1 - 30/31)
         * m = Month number
         * Y = Year in 4 digits (1999)
         */
        $seconds = 0;
        $v = explode(",", array_shift($args));
        foreach($v as $t){
            if(strpos($t, "s")){
                $time = substr($t, -2);
                $seconds += (is_numeric($time) ? $time : 0);
            }
            if(strpos($t, "m")){
                $time = substr($t, -2) * 60;
                $seconds += (is_numeric($time) ? $time : 0);
            }
            if(strpos($t, "h")){
                $time = substr($t, -2) * 60 * 60;
                $seconds += (is_numeric($time) ? $time : 0);
            }
            if(strpos($t, "d")){
                $time = substr($t, -2) * 24 * 60 * 60;
                $seconds += (is_numeric($time) ? $time : 0);
            }
            if(strpos($t, "w")){
                $time = substr($t, -2) * 7 * 24 * 60 * 60;
                $seconds += (is_numeric($time) ? $time : 0);
            }
            if(strpos($t, "mo")){
                $time = substr($t, -3) * 30 * 24 * 60 * 60;
                $seconds += (is_numeric($time) ? $time : 0);
            }
            if(strpos($t, "y")){
                $time = substr($t, -2) * 365 * 24 * 60 * 60;
                $seconds += (is_numeric($time) ? $time : 0);
            }
        }
        $reason = implode(" ", $args);
        $date = new \DateTime();
        $date->setTimestamp($time = time() + $seconds);
        if($player !== false){
            if($player->hasPermission("essentials.banexempt")){
                $sender->sendMessage(TextFormat::RED . "[Error] $name can't be banned");
                return false;
            }else{
                $name = $player->getName();
                $player->kick(TextFormat::RED . "Banned until " . TextFormat::AQUA . date("l, F j, Y", $time) . TextFormat::RED . " at " . TextFormat::AQUA . date("h:1a", $time));
            }
        }
        $sender->getServer()->getNameBans()->addBan($name, ($reason !== "" ? $reason : null), $date, "essentialspe");

        $this->broadcastCommandMessage($sender, "Banned player " . $name);
        return true;
    }
}
