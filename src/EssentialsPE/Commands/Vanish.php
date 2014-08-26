<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Vanish extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "vanish", "Hide from other players!", "/vanish [player]", ["v"]);
        $this->setPermission("essentials.vanish.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) > 1){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /vanish <player>");
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /vanish <player>");
                }else{
                    $this->getAPI()->switchVanish($sender);
                    if(!$this->getAPI()->isVanished($sender)){
                        $sender->sendMessage(TextFormat::GRAY . "You're now visible");
                    }else{
                        $sender->sendMessage(TextFormat::GRAY . "You're now vanished!");
                    }
                }
                return true;
                break;
            case 1:
                $player = $this->getAPI()->getPlayer($args[0]);
                if($player == false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                }else{
                    $this->getAPI()->switchVanish($player);
                    if(!$this->getAPI()->isVanished($player)){
                        $player->sendMessage(TextFormat::GRAY . "You're now visible");
                        $sender->sendMessage(TextFormat::GRAY . "$args[0] is now visible");
                    }else{
                        $player->sendMessage(TextFormat::GRAY . "You're now vanished!");
                        $sender->sendMessage(TextFormat::GRAY . "$args[0] is now vanished!");
                    }
                }
                return true;
                break;
        }
        return true;
    }
}
