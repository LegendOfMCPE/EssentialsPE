<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class World extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "world", "Teleport between worlds", "<world name>", false);
        $this->setPermission("essentials.world");
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $alias, array $args): bool{
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player || count($args) !== 1){
            $this->sendUsage($sender, $alias);
            return false;
        }
        if(!$sender->hasPermission("essentials.worlds.*") && !$sender->hasPermission("essentials.worlds." . strtolower($args[0]))){
            $sender->sendMessage(TextFormat::RED . "[Error] You can't teleport to this world.");
            return false;
        }
        if(!$sender->getServer()->isLevelGenerated($args[0])){
            $sender->sendMessage(TextFormat::RED . "[Error] World doesn't exist");
            return false;
        }elseif(!$sender->getServer()->isLevelLoaded($args[0])){
            $sender->sendMessage(TextFormat::YELLOW . "Level is not loaded yet. Loading...");
            if(!$sender->getServer()->loadLevel($args[0])){
                $sender->sendMessage(TextFormat::RED . "[Error] The level couldn't be loaded");
                return false;
            }
        }
        $sender->teleport($this->getAPI()->getServer()->getLevelByName($args[0])->getSafeSpawn());
        $sender->sendMessage(TextFormat::YELLOW . "Teleporting...");
        return true;
    }
} 
