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
        $this->setPermission("essentials.clearinventory");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /clearinventory <player>");
                    return false;
                }
                if($sender->getServer()->getGamemodeString($sender->getGamemode()) === 1|3){
                    $sender->sendMessage(TextFormat::RED . "[Error] You're in " . ($sender->getServer()->getGamemodeString($sender->getGamemode()) === 1 ? "creative" : "adventure") . " mode");
                    return false;
                }
                $sender->getInventory()->clearAll();
                $sender->sendMessage(TextFormat::AQUA . "Your inventory was cleared");
                break;
            case 1:
                if(!$sender->hasPermission("essentials.clearinventory.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                    return false;
                }
                if($sender->getServer()->getGamemodeString($player->getGamemode()) === 1|3){
                    $sender->sendMessage(TextFormat::RED . "[Error] $args[0] is in " . ($sender->getServer()->getGamemodeString($player->getGamemode()) === 1 ? "creative" : "adventure") . " mode");
                    return false;
                }
                $player->getInventory()->clearAll();
                $sender->sendMessage(TextFormat::AQUA . "$args[0]'" . (substr($args[0], -1, 1) === "s" ? "" : "s") . " inventory was cleared");
                $player->sendMessage(TextFormat::AQUA . "Your inventory was cleared");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $sender instanceof Player ? $this->getUsage() : "Usage: /clearinventory <player>");
                return false;
                break;
        }
        return true;
    }
}
