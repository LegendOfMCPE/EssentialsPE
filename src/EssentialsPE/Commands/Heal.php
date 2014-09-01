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
        $this->setPermission("essentials.heal");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /heal <player>");
                    return false;
                }
                $sender->heal($sender->getMaxHealth());
                $sender->sendMessage(TextFormat::GREEN . "You have been healed!");
                break;
            case 1:
                if(!$sender->hasPermission("essentials.heal.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }
                $player->heal($player->getMaxHealth());
                $sender->sendMessage(TextFormat::GREEN . "$args[0] has been healed!");
                $player->sendMessage(TextFormat::GREEN . "You have been healed!");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $sender instanceof Player ? $this->getUsage() : "Usage: /heal <player>");
                return false;
                break;
        }
        return true;
    }
}
