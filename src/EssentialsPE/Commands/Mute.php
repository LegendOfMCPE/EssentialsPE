<?php

namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Mute extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "mute", "Prevent a player from chatting", "/mute <player>", ["silence"]);
        $this->setPermission("essentials.mute");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) != 1){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            return false;
        }
        $player = $this->getAPI()->getPlayer($args[0]);
        if($player === false){
            $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
        }else{
            if($player->hasPermission("essentials.mute.exempt")){
                if(!$this->getAPI()->isMuted($player)){
                    $sender->sendMessage(TextFormat::RED . "$args[0] can't be muted");
                    return false;
                }
            }
            $this->getAPI()->switchMute($player);
            if(!$this->getAPI()->isMuted($player)){
                $sender->sendMessage(TextFormat::YELLOW . "$args[0] has been unmuted!");
            }else{
                $sender->sendMessage(TextFormat::YELLOW . "$args[0] has been muted!");
            }
        }
        return true;
    }
} 