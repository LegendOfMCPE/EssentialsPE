<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class World extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "world", "Teleport between worlds", "/world <world name>");
        $this->setPermission("essentials.world");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        switch(count($args)){
            case 1:
                if(!$sender->getServer()->isLevelGenerated($args[0]) || !$sender->getServer()->isLevelLoaded($args[0])){
                    $sender->sendMessage(TextFormat::RED . "[Error] World " . ($sender->getServer()->isLevelGenerated($args[0]) ? "is not loaded" : "not found"));
                    return false;
                }elseif(strtolower($sender->getLevel()->getName()) === strtolower($args[0])){
                    $sender->sendMessage(TextFormat::RED . "You're already in world $args[0]");
                    return false;
                }
                $world = $sender->getServer()->getLevelByName($args[0]);
                $sender->teleport(new Position($sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ(), $world), 0, 0);
                $sender->sendMessage(TextFormat::YELLOW . "Teleporting...");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $this->getUsage());
                break;
        }
        return true;
    }
} 