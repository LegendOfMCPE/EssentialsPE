<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Heal extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "heal", "Heal yourself or other player", "/heal [player]");
        $this->setPermission("essentials.heal.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) > 1){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /heal <player>");
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /heal <player>");
                }else{
                    $sender->heal($sender->getMaxHealth());
                    $sender->sendMessage(TextFormat::GREEN . "You have been healed!");
                }
                break;
            case 1:
                if(!$sender->hasPermission("essentials.heal.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                }else{
                    $player = $this->getAPI()->getPlayer($args[0]);
                    if($player === false){
                        $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                    }else{
                        $player->heal($player->getMaxHealth());
                        $player->sendMessage(TextFormat::GREEN . "You have been healed!");
                        $sender->sendMessage(TextFormat::GREEN . "$args[0] has been healed!");
                    }
                }
                break;
        }
        return true;
    }
}
