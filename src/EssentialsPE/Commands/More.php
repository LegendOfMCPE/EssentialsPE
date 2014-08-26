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
        $this->setPermission("essentials.more.use");
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
        $inv = $sender->getInventory();
        $item = $inv->getItemInHand();
        if($item->getID() === 0){
            $sender->sendMessage(TextFormat::RED . "You can't get a stack of AIR");
            return false;
        }
        if(!$sender->hasPermission("essentials.more.oversizedstacks")){
            $item->setCount($item->getMaxStackSize());
            $inv->setItemInHand($item);
        }else{
            $item->setCount(64);
            $inv->setItemInHand($item);
        }
        return true;
    }
}
