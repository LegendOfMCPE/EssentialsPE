<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Condense extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "condense", "Compact your inventory!", "[item name|id|hand|inventory|all]", false, ["compact", "toblocks"]);
        $this->setPermission("essentials.condense");
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
        if(!$sender instanceof Player){
            $this->sendUsage($sender, $alias);
            return false;
        }
        if(!isset($args[0])){
            $args[0] = "inventory";
        }
        switch($args[0]){
            case "hand":
                $target = $sender->getInventory()->getItemInHand();
                break;
            case "inventory":
            case "all":
                $target = null;
                break;
            default: // Item name|id
                $target = $this->getAPI()->getItem($args[0]);
                if($target->getId() === 0){
                    $sender->sendMessage(TextFormat::RED . "Unknown item \"" . $args[0] . "\"");
                    return true;
                }
                break;
        }
        if(!$this->getAPI()->condenseItems($sender->getInventory(), $target)){
            $sender->sendMessage(TextFormat::RED . "[Error] This item can't be condensed");
            return false;
        }
        $sender->sendMessage(TextFormat::YELLOW . "Condensing items...");
        return true;
    }
}
