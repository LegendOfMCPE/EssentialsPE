<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RealName extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "realname", "Check the realname of a player", "/realname <player>");
        $this->setPermission("essentials.realname");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) != 1){
            $sender->sendMessage(TextFormat::RED . ($sender instanceof Player ? "" : "Usage: ") . $this->getUsage());
            return false;
        }
        $player = $this->getPlugin()->getPlayer($args[0]);
        if($player === false){
            $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
            return false;
        }
        $sender->sendMessage(TextFormat::YELLOW . "$args[0]'" . (substr($args[0], -1, 1) === "s" ? "" : "s") . "realname is " . TextFormat::RED . $player->getName());
        return true;
    }
}
