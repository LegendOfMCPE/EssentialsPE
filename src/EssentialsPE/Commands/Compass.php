<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Compass extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "compass", "Display your current bearing direction", "/compass", ["direction"]);
        $this->setPermission("essentials.compass");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
        }
        if(count($args) > 0){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
        }

        $direction = "";
        if($sender->getDirection() == 0){
            $direction = "south";
        }elseif($sender->getDirection() == 1){
            $direction = "west";
        }elseif($sender->getDirection() == 2){
            $direction = "north";
        }elseif($sender->getDirection() == 3){
            $direction = "east";
        }

        $sender->sendMessage(TextFormat::AQUA . "You're facing $direction");
        return true;
    }
}
