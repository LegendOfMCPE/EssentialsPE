<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SetSpawn extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "setspawn", "Change your server main spawn point", "/setspawn");
        $this->setPermission("essentials.setspawn");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }
        if(count($args) != 0){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }
        $sender->getLevel()->setSpawnLocation(new Vector3($sender->getFloorX(), $sender->getFloorY(), $sender->getFloorZ()));
        $sender->getServer()->setDefaultLevel($sender->getLevel());
        $sender->sendMessage(TextFormat::YELLOW . "Spawn point changed!");
        return true;
    }
}
