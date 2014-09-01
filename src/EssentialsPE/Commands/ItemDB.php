<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ItemDB extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "itemdb", "Display the information attached to the item you hold", "/itemdb [name, id, meta]", ["itemno", "durability", "dura"]);
        $this->setPermission("essentials.itemdb");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Pleas run this command in-game");
            return false;
        }
        $item = $sender->getInventory()->getItemInHand();
        switch(count($args)){
            case 0:
                $sender->sendMessage(TextFormat::AQUA . "This item " . ($this->getPlugin()->isReparable($item) ? "has " . TextFormat::RED . $item->getDamage() . TextFormat::AQUA . " points of damage" : "metadata is " . TextFormat::RED . $item->getDamage()));
                break;
            case 1:
                switch(strtolower($args)){
                    case "name":
                        $sender->sendMessage(TextFormat::AQUA . "This item is named: " . TextFormat::RED . $item->getName());
                        break;
                    case "id":
                        $sender->sendMessage(TextFormat::AQUA . "This item ID is: " . TextFormat::RED . $item->getID());
                        break;
                    case "durability":
                    case "dura":
                    case "metadata":
                    case "meta":
                        $sender->sendMessage(TextFormat::AQUA . "This item " . ($this->getPlugin()->isReparable($item) ? "has " . TextFormat::RED . $item->getDamage() . TextFormat::AQUA . " points of damage" : "metadata is " . TextFormat::RED . $item->getDamage()));
                        break;
                }
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $this->getUsage());
                return false;
                break;
        }
        return true;
    }
} 