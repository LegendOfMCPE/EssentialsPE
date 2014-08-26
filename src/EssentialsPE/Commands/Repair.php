<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Repair extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "repair", "Repair the item you're holding", "/repair [all]", ["fix"]);
        $this->setPermission("essentials.repair");
    }

    public function execute(CommandSender $sender, $alias,  array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }elseif(count($args) > 1){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }
        switch(count($args)){
            case 0:
                $inv = $sender->getInventory();
                $item = $inv->getItemInHand();
                if(!$this->isReparable($item)){
                    $sender->sendMessage(TextFormat::RED . "[Error] This item can't be repaired!");
                    return false;
                }
                $item->setDamage(0);
                $inv->setItemInHand($item);
                $sender->sendMessage(TextFormat::GREEN . "Item successfully repaired!");
                break;
            case 1:
                switch(strtolower($args[0])){
                    case "all":
                        if(!$sender->hasPermission("essentials.repair.all")){
                            $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                            return false;
                        }
                        $inv = $sender->getInventory();
                        foreach($inv->getContents() as $item){
                            if($this->isReparable($item)){
                                $item->setDamage(0);
                            }
                        }
                        $r = "All the tools on your inventory were repaired!";
                        if($sender->hasPermission("essentials.repair.armor")){
                            foreach($inv->getArmorContents() as $item){
                                $item->setDamage(0);
                            }
                            $r .= "\n(including the equipped Armor)";
                        }
                        $sender->sendMessage(TextFormat::GREEN . $r);
                        break;
                    case "hand":
                        $inv = $sender->getInventory();
                        $item = $inv->getItemInHand();
                        if(!$this->isReparable($item)){
                            $sender->sendMessage(TextFormat::RED . "[Error] This item can't be repaired!");
                            return false;
                        }
                        $item->setDamage(0);
                        $inv->setItemInHand($item);
                        $sender->sendMessage(TextFormat::GREEN . "Item successfully repaired!");
                        break;
                    default:
                        $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
                        return false;
                        break;
                }
        }
        return true;
    }

    private function isReparable(Item $item){
        if(!$item->isTool() || $item->getID() !== Item::CHAIN_BOOTS|Item::CHAIN_LEGGINGS|Item::CHAIN_CHESTPLATE|Item::CHAIN_HELMET|Item::LEATHER_BOOTS|Item::LEATHER_PANTS|Item::LEATHER_TUNIC|Item::LEATHER_CAP|Item::IRON_BOOTS|Item::IRON_LEGGINGS|Item::IRON_CHESTPLATE|Item::IRON_HELMET|Item::GOLD_BOOTS|Item::GOLD_LEGGINGS|Item::GOLD_CHESTPLATE|Item::GOLD_HELMET|Item::DIAMOND_BOOTS|Item::DIAMOND_LEGGINGS|Item::DIAMOND_CHESTPLATE|Item::DIAMOND_HELMET){
            return false;
        }
        return true;
    }
}
