<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ClearInventory extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "clearinventory", "Clear your/other's inventory", "/clearinventory [player]", ["ci", "clean", "clearinvent"]);
        $this->setPermission("essentials.clearinventory.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) > 1){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /clearinventory <player>");
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /clearinventory <player>");
                }else{
                    $sender->getInventory()->clearAll();
                    $sender->sendMessage(TextFormat::AQUA . "Your inventory was cleared");
                }
                break;
            case 1:
                if(!$sender->hasPermission("essentials.clearinventory.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }else{
                    $player = $this->getAPI()->getPlayer($args[0]);
                    if($player === false){
                        $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                    }else{
                        $player->getInventory()->clearAll();
                        $player->sendMessage(TextFormat::AQUA . "Your inventory was cleared");
                        if(substr($player->getDisplayName(), -1, 1) != "s"){
                            $sender->sendMessage(TextFormat::GREEN . "$args[0]'s inventory was cleared");
                        }else{
                            $sender->sendMessage(TextFormat::GREEN . "$args[0]' inventory was cleared");
                        }
                    }
                }
                break;
        }
        return true;
    }
}
