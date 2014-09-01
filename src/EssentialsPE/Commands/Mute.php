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
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return false;
        }
        $player = $this->getPlugin()->getPlayer($args[0]);
        if($player === false){
            $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
            return false;
        }
        if($player->hasPermission("essentials.mute.exempt")){
            if(!$this->getPlugin()->isMuted($player)){
                $sender->sendMessage(TextFormat::RED . "$args[0] can't be muted");
                return false;
            }
        }
        $this->getPlugin()->switchMute($player);
        $sender->sendMessage(TextFormat::YELLOW . "$args[0] has been " . ($this->getPlugin()->isMuted($player) ? "muted!" : "unmuted!"));
        return true;
    }
} 
