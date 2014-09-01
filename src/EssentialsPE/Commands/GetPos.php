<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GetPos extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "getpos", "Get your/other's position", "/getpos [player]", ["coords", "position", "whereami", "getlocation", "getloc"]);
        $this->setPermission("essentials.getpos");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /getpos <player>");
                    return false;
                }
                $pos = $sender->getPosition();
                $sender->sendMessage(TextFormat::GREEN . "You're in world: " . TextFormat::AQUA . $sender->getLevel()->getName() . "\n" . TextFormat::GREEN . "Your Coordinates are:" . TextFormat::YELLOW . " X: " . TextFormat::AQUA . floor($pos->x) . TextFormat::GREEN . "," . TextFormat::YELLOW . " Y: " . TextFormat::AQUA . floor($pos->y) . TextFormat::GREEN . "," . TextFormat::YELLOW . " Z: " . TextFormat::AQUA . floor($pos->z));
                break;
            case 1:
                if(!$sender->hasPermission("essentials.getpos.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                }else{
                    $pos = $player->getPosition();
                    $sender->sendMessage(TextFormat::YELLOW . $args[0] . TextFormat::GREEN . " is in world: " . TextFormat::AQUA . $player->getLevel()->getName() . "\n" . TextFormat::GREEN . "Coordinates:" . TextFormat::YELLOW . " X: " . TextFormat::AQUA . floor($pos->x) . TextFormat::GREEN . "," . TextFormat::YELLOW . " Y: " . TextFormat::AQUA . floor($pos->y) . TextFormat::GREEN . "," . TextFormat::YELLOW . " Z: " . TextFormat::AQUA . floor($pos->z));
                }
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $sender instanceof Player ? $this->getUsage() : "Usage: /getpos <player>");
                return false;
                break;
        }
        return true;
    }
}
