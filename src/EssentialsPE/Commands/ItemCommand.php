<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ItemCommand extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "item", "Gives yourself an item", "/item <item[:damage]> [amount]", ["i"]);
        $this->setPermission("essentials.item");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        if(($gm = $sender->getServer()->getGamemodeString($sender->getGamemode())) === "CREATIVE" || $gm === "SPECTATOR"){
            $sender->sendMessage(TextFormat::RED . "[Error] You're in " . strtolower($gm) . " mode");
            return false;
        }
        if(count($args) < 1 || count($args) > 2){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }

        //Getting the item...
        if(strpos($args[0], ":") !== false){
            $v = explode(":", $args[0]);
            $item_name = $v[0];
            $damage = $v[1];
        }else{
            $item_name = $args[0];
            $damage = 0;
        }
        unset($args[0]); //In case of needing to get other values (Example below)

        if(!is_numeric($item_name)){
            $item = Item::fromString($item_name);
        }else{
            $item = Item::get($item_name);
        }
        $item->setDamage($damage);

        if($item->getID() === 0){
            $sender->sendMessage(TextFormat::RED . "Unknown item \"" . $item_name . "\"");
            return false;
        }elseif(!$sender->hasPermission("essentials.itemspawn.item-all") || !$sender->hasPermission("essentials.itemspawn.item-" . $item->getName() || !$sender->hasPermission("essentials.itemspawn.item-" . $item->getID()))){
            $sender->sendMessage(TextFormat::RED . "You can't spawn this item");
            return false;
        }

        //Setting the amount...
        if(!isset($args[1]) || !is_numeric($args[1])){
            if(!$sender->hasPermission("essentials.oversizedstacks")){
                $item->setCount($item->getMaxStackSize());
            }else{
                $item->setCount($this->getPlugin()->getConfig()->get("oversized-stacks"));
            }
        }else{
            $item->setCount($args[1]);
        }
        unset($args[1]); //In case of needing to get other values (Example below)

        //Getting other values...
        /*foreach($args as $a){
            //Example
            if(stripos(strtolower($a), "color") !== false){
                $v = explode(":", $a);
                $color = $v[1];
            }
        }*/

        //Giving the item...
        $sender->getInventory()->addItem($item);
        $sender->sendMessage(TextFormat::YELLOW . "Giving " . TextFormat::RED . $item->getCount() . TextFormat::YELLOW . " of " . TextFormat::RED . $item->getName() === "Unknown" ? $item_name : $item->getName());
        return false;
    }
}
