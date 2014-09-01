<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Invsee extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "invsee", "See other players' inventory", "/invsee [player]");
        $this->setPermission("essentials.invsee");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        if(($gm = strtolower($sender->getServer()->getGamemodeString($sender->getGamemode()))) === "creative" || $gm === "spectator"){
            $sender->sendMessage(TextFormat::RED . "You can only perfom this command if you are on Survival or Spectator mode");
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$this->getPlugin()->isPlayerWatchingOtherInventory($sender)){
                    $sender->sendMessage(TextFormat::RED . $this->getUsage());
                    return false;
                }
                $this->getPlugin()->restorePlayerInventory($sender);
                $sender->sendMessage(TextFormat::AQUA . "Your inventory was restored!");
                break;
            case 1:
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }elseif(($gm = strtolower($sender->getServer()->getGamemodeString($player->getGamemode()))) === "creative" || $gm === "spectator"){
                    $sender->sendMessage(TextFormat::RED . "Player is on " . ($gm === "creative" ? "creative" : "spectator") . "mode");
                    return false;
                }
                $this->getPlugin()->setPlayerInventory($sender, $player);
                $sender->sendMessage(TextFormat::GREEN . "You're now watching $args[0]'" . (substr($args[0], -1, 1) === "s" ? "" : "s") . " inventory" . TextFormat::YELLOW . "\nTo restore your inventory run: " . TextFormat::AQUA . "/invsee");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $this->getUsage());
                return false;
                break;
        }
        //TODO Handle Inventory modifications with permission nodes and Events
        return true;
    }
} 