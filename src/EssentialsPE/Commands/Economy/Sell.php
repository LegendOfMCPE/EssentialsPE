<?php
namespace EssentialsPE\Commands\Economy;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Sell extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "sell", "Sell the specified item", "<item|hand> [amount]", false);
        $this->setPermission("essentials.sell");
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $this->sendUsage($sender, $alias);
            return false;
        }
        if($sender->getGamemode() === Player::CREATIVE || $sender->getGamemode() === Player::SPECTATOR){
            $sender->sendMessage(TextFormat::RED . "[Error] You're in " . $this->getAPI()->getServer()->getGamemodeString($sender->getGamemode()) . " mode");
            return false;
        }
        if(strtolower($args[0]) === "hand"){
            $item = $sender->getInventory()->getItemInHand();
            if($item->getId() === 0){
                $sender->sendMessage(TextFormat::RED . "[Error] You don't have anything in your hand");
                return false;
            }
        }else{
            if(!is_int($args[0])){
                $item = Item::fromString($args[0]);
            }else{
                $item = Item::get($args[0]);
            }
            if($item->getId() === 0){
                $sender->sendMessage(TextFormat::RED . "[Error] Unknown item");
                return false;
            }
        }
        if(!$sender->getInventory()->contains($item)){
            $sender->sendMessage(TextFormat::RED . "[Error] You don't have that item in your inventory");
            return false;
        }
        if(isset($args[1]) && !is_numeric($args[1])){
            $sender->sendMessage(TextFormat::RED . "[Error] Please specify a valid amount to sell");
            return false;
        }

        $amount = $this->getAPI()->sellPlayerItem($sender, $item, $args[1] ?? null);
        if(!$amount){
            $sender->sendMessage(TextFormat::RED . "[Error] Worth not available for this item");
            return false;
        }elseif($amount === -1){
            $sender->sendMessage(TextFormat::RED . "[Error] You don't have that amount of items");
            return false;
        }

        if(is_array($amount)){
            $sender->sendMessage(TextFormat::RED . "Sold " . $amount[0] . " items! You got" . $this->getAPI()->getCurrencySymbol() . ($amount[1] * $amount[0]));
        }else{
            $sender->sendMessage(TextFormat::GREEN . "Item sold! You got " . $this->getAPI()->getCurrencySymbol() . $amount);
        }
        return true;
    }
}