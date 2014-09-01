<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Extinguish extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "extinguish", "Extinguish a player", "/extinguish [player]", ["ext"]);
        $this->setPermission("essentials.extinguish");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /extinguish <player>");
                    return false;
                }
                $sender->extinguish();
                $sender->sendMessage(TextFormat::AQUA . "You were extinguished!");
                break;
            case 1:
                if(!$sender->hasPermission("essentials.extinguish.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                }else{
                    $player->extinguish();
                    $sender->sendMessage(TextFormat::AQUA . "$args[0] has been extinguished!");
                }
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $sender instanceof Player ? $this->getUsage() : "Usage: /extinguish <player>");
                return false;
                break;
        }
        return true;
    }
}
