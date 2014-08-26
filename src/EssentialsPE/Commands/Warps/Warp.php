<?php
namespace EssentialsPE\Commands\Warps;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Warp extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "warp", "Teleport to a warp", "/warp <name> [player]", ["warps"]);
        $this->setPermission("essentials.warp.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) < 1 || count($args) > 2){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /warp <name> <player>");
                return false;
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /warp <name> <player>");
                    return false;
                }
                $sender->sendMessage(TextFormat::RED . "Usage: /warp <name> [player]]");
                return true;
                break;
            case 1:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /warp <name> <player>");
                    return false;
                }
                if(!$this->getAPI()->warpExist($args[0])){
                    $sender->sendMessage(TextFormat::RED . "[Error] Unknown warp name.");
                }else{
                    $this->getAPI()->tpWarp($sender, $args[0]);
                    $sender->sendMessage(TextFormat::YELLOW . "Teleporting to warp: $args[0]");
                }
                return true;
                break;
            case 2:
                $player = $this->getAPI()->getPlayer($args[1]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                }else{
                    if(!$this->getAPI()->warpExist($args[0])){
                        $sender->sendMessage(TextFormat::RED . "[Error] Unknown warp name.");
                    }else{
                        $sender->sendMessage(TextFormat::YELLOW . "Teleporting $args[1] to warp: $args[0]");
                        $player->sendMessage(TextFormat::YELLOW . "Teleporting to warp: $args[0]");
                        $this->getAPI()->tpWarp($player, $args[0]);
                    }
                }
                return true;
                break;
        }
        return true;
    }
} 