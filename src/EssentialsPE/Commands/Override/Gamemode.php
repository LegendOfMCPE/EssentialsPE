<?php
namespace EssentialsPE\Commands\Override;

use EssentialsPE\BaseFiles\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Gamemode extends BaseCommand{
    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "gamemode", "Change player gamemode", "<mode> [player]", null, ["gm", "gma", "gmc", "gms", "gmt", "adventure", "creative", "survival", "spectator", "viewer"]);
        $this->setPermission("essentials.gamemode");
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(strtolower($alias) !== "gamemode" && strtolower($alias) !== "gm"){
            if(isset($args[0])){
                $args[1] = $args[0];
                unset($args[0]);
            }
            switch(strtolower($alias)){
                case "survival":
                case "gms":
                case "0":
                    $args[0] = "survival";
                    break;
                case "creative":
                case "gmc":
                case "1":
                    $args[0] = "creative";
                    break;
                case "adventure":
                case "gma":
                case "2":
                    $args[0] = "adventure";
                    break;
                case "spectator":
                case "viewer":
                case "gmt":
                case "3":
                    $args[0] = "spectator";
                    break;
                default:
                    return false;
                    break;
            }
        }
        if(count($args) < 1){
            $this->sendUsage($sender, $alias);
            return false;
        }
        $player = $sender;
        if(!$player instanceof Player && !isset($args[1])){
            $this->sendUsage($sender, $alias);
            return false;
        }
        if(isset($args[1])){
            $player = $this->getPlugin()->getPlayer($args[1]);
            if(!$player){
                $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                return false;
            }
        }

        /**
         * The following switch is applied when the user execute:
         * /gamemode <MODE>
         */
        if(is_int($args[0])){
            switch($args[0]){
                case 0:
                case 1:
                case 2:
                case 3:
                    $gm = $args[0];
                    break;
                default:
                    $sender->sendMessage(TextFormat::RED . "[Error] Please specify a valid gamemode");
                    return false;
                    break;
            }
        }else{
            switch(strtolower($args[0])){
                case "survival":
                case "s":
                case "0":
                    $gm = 0;
                    break;
                case "creative":
                case "c":
                case "1":
                    $gm = 1;
                    break;
                case "adventure":
                case "a":
                case "2":
                    $gm = 2;
                    break;
                case "spectator":
                case "viewer":
                case "view":
                case "v":
                case "t":
                case "3":
                    $gm = 3;
                    break;
                default:
                    $sender->sendMessage(TextFormat::RED . "[Error] Please specify a valid gamemode");
                    return false;
                    break;
            }
        }
        $gmstring = $this->getPlugin()->getServer()->getGamemodeString($gm);
        if($player->getGamemode() === $gm){
            $player->sendMessage(TextFormat::RED . "[Error] " . ($player === $sender ? "You're" : $args[1] . " is") . " already in " . $gmstring . " mode");
            return false;
        }
        if($player !== $sender){
            $sender->sendMessage(TextFormat::GREEN . $args[1] . " is now in " . $gmstring . " mode");
        }
        $player->setGamemode($gm);
        return true;
    }
} 
