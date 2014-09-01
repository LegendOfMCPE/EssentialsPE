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
        $this->setPermission("essentials.unlimited");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /unlimited <player>");
                    return false;
                }
                if($sender->getServer()->getGamemodeString($sender->getGamemode()) === 1|3){
                    $sender->sendMessage(TextFormat::RED . "[Error] You're in " . ($sender->getServer()->getGamemodeString($sender->getGamemode()) === 1 ? "creative" : "adventure") . " mode");
                    return false;
                }
                $this->getPlugin()->switchUnlimited($sender);
                $sender->sendMessage(TextFormat::GREEN . "Unlimited placing of blocks " . ($this->getPlugin()->isUnlimitedEnabled($sender) ? "enabled!" : "disabled!"));
                break;
            case 1:
                if(!$sender->hasPermission("essentials.unlimited.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }
                if($sender->getServer()->getGamemodeString($player->getGamemode()) === 1|3){
                    $sender->sendMessage(TextFormat::RED . "[Error] $args[0] is in " . ($sender->getServer()->getGamemodeString($player->getGamemode()) === 1 ? "creative" : "adventure") . " mode");
                    return false;
                }
                $this->getPlugin()->switchUnlimited($player);
                $sender->sendMessage(TextFormat::GREEN . "Unlimited placing of blocks " . ($this->getPlugin()->isUnlimitedEnabled($player) ? "enabled" : "disabled") . " for player $args[0]");
                $player->sendMessage(TextFormat::GREEN . "Unlimited placing of blocks " . ($this->getPlugin()->isUnlimitedEnabled($player) ? "enabled!" : "disabled!"));
                break;
            default:
                $sender->sendMessage(TextFormat::RED . ($sender instanceof Player ? "" : "Usage: ") . $this->getUsage());
                return false;
                break;
        }
        return true;
    }
} 