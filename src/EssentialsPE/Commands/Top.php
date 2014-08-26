<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Top extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "top", "Teleport to the highest block above you", "/top");
        $this->setPermission("essentials.top");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }
        if(count($args) != 0){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }
        $block = $sender->getLevel()->getHighestBlockAt($sender->getX(), $sender->getZ());
        $pos = new Vector3($sender->getX(), ($block + 1), $sender->getZ());
        $sender->sendMessage(TextFormat::YELLOW . "Teleporting...");
        $sender->teleport($pos);
        return true;
    }
}
