<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Seen extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "seen", "See player's last played time", "/seen <player>");
        $this->setPermission("essentials.seen");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) !== 1){
            $sender->sendMessage(TextFormat::RED . ($sender instanceof Player ? "" : "Usage: ") . $this->getUsage());
            return false;
        }
        $player = $this->getPlugin()->getPlayer($args[0]);
        if($player !== false){
            $sender->sendMessage(TextFormat::GREEN . $player->getDisplayName() . " is online!");
            return false;
        }
        if(!is_numeric($sender->getServer()->getOfflinePlayer($args[0])->getLastPlayed())){
            $sender->sendMessage(TextFormat::RED . "$args[0] never played on this server.");
            return false;
        }
        /**
         * a = am/pm
         * i = Minutes
         * h = Hour (12 hours format with leading zeros)
         * l = Day name
         * j = Day number (1 - 30/31)
         * F = Month name
         * Y = Year in 4 digits (1999)
         */
        $ptime = $sender->getServer()->getOfflinePlayer($args[0])->getLastPlayed() / 1000;
        $sender->sendMessage(TextFormat::AQUA . "$args[0] was last seen on " . TextFormat::RED . date("l, F j, Y", $ptime) . TextFormat::AQUA . " at " . TextFormat::RED . date("h:ia", $ptime));
        return true;
    }
}
