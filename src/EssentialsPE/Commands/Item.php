<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Item extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "item", "Gives yourself an item", "/item <item[:damage|metadata]> [amount]", ["i"]);
        $this->setPermission("essentials.item");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }elseif(count($args) < 1 || count($args) > 2){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }

        if(strrpos($args[0], ":") === false){
            $item = substr($args[0], 0, strrpos($args[0], ":") - 1);
            $meta = substr($args[0], -1, strrpos($args[0], ":") - 1);
        }else{
            $item = $args[0];
            $meta = 0;
        }

        if(!is_numeric($item)){
            $item = \pocketmine\item\Item::fromString($item);
        }else{
            $item = \pocketmine\item\Item::get($item, $meta);
        }

        if($item->getID() === 0){
            $sender->sendMessage(TextFormat::RED . "Unknown item");
            return false;
        }

        if(!isset($args[1]) || !is_numeric($args[1])){
            $item->setCount($item->getMaxStackSize());
        }else{
            $item->setCount($args[1]);
        }

        $sender->getInventory()->addItem($item);
        $sender->sendMessage(TextFormat::YELLOW . "Giving " . $item->getCount() . " of " . $item->getName());
        return false;
    }
}
