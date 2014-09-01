<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class More extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "more", "Get a stack of the item you're holding", "/more");
        $this->setPermission("essentials.more");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }
        if(($gm = $sender->getServer()->getGamemodeString($sender->getGamemode())) === "CREATIVE" || $gm === "SPECTATOR"){
            $sender->sendMessage(TextFormat::RED . "[Error] You're in " . strtolower($gm) . " mode");
            return false;
        }
        if(count($args) != 0){
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return false;
        }
        $item = $sender->getInventory()->getItemInHand();
        if($item->getID() === 0){
            $sender->sendMessage(TextFormat::RED . "You can't get a stack of AIR");
            return false;
        }
        $item->setCount(($sender->hasPermission("essentials.oversizedstacks") ? $this->getPlugin()->getConfig()->get("oversized-stacks") : $item->getMaxStackSize()));
        return true;
    }
}
