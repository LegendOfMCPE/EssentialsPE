<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Unlimited extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "unlimited", "Allow you to place unlimited blocks", "/unlimited [player]", ["ul", "unl"]);
        $this->setPermission("essentials.unlimited.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) > 1){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /unlimited <player>");
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /unlimited <player>");
                    return false;
                }
                $this->getAPI()->switchUnlimited($sender);
                if(!$this->getAPI()->isUnlimitedEnabled($sender)){
                    $sender->sendMessage(TextFormat::GREEN . "Unlimited place of block disabled!");
                }else{
                    $sender->sendMessage(TextFormat::GREEN . "Unlimited place of block enabled!");
                }
                return true;
                break;
            case 1:
                if(!$sender->hasPermission("essentials.unlimited.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getAPI()->getPlayer($args[0]);
                if(!$player instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }
                $this->getAPI()->switchUnlimited($player);
                if(!$this->getAPI()->isUnlimitedEnabled($player)){
                    $sender->sendMessage(TextFormat::GREEN . "Unlimited place of block disabled for player " . $args[0]);
                    $player->sendMessage(TextFormat::GREEN . "Unlimited place of block disabled!");
                }else{
                    $sender->sendMessage(TextFormat::GREEN . "Unlimited place of block enabled for player " . $args[0]);
                    $player->sendMessage(TextFormat::GREEN . "Unlimited place of block enabled!");
                }
                return true;
                break;
        }
        return true;
    }
} 