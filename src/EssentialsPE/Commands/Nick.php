<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Nick extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "nick", "Change your in-game name", "/nick <new nick|off> [player]", ["nickname"]);
        $this->setPermission("essentials.nick.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) == 0 || count($args) > 2){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /nick <new nick|off> <player>");
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
        }else{
            switch(count($args)){
                case 1:
                    $nickname = $args[0];
                    if(!$sender instanceof Player){
                        $sender->sendMessage(TextFormat::RED . "Usage: /nick <new nick|off> <player>");
                    }else{
                        if($nickname == "off"){
                            $this->getAPI()->removeNick($sender, true);
                        }else{
                            $this->getAPI()->setNick($sender, $nickname, true);
                        }
                    }
                    break;
                case 2:
                    if(!$sender->hasPermission("essentials.nick.other")){
                        $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    }else{
                        $nickname = $args[0];
                        $player = $this->getAPI()->getPlayer($args[1]);
                        if($player == false){
                            $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                        }else{
                            if($nickname == "off"){
                                if($player->getName() != $sender->getName()){
                                    if(substr($player->getDisplayName(), -1, 1) == "s"){
                                        $sender->sendMessage(TextFormat::GREEN . "$args[1]' nick has been disabled");
                                    }else{
                                        $sender->sendMessage(TextFormat::GREEN . "$args[1]'s nick has been disabled");
                                    }
                                }
                                $this->getAPI()->removeNick($player, true);
                            }else{
                                if($player->getName() != $sender->getName()){
                                    if(substr($player->getDisplayName(), -1, 1) == "s"){
                                        $sender->sendMessage(TextFormat::GREEN . "$args[1]' nick has been changed");
                                    }else{
                                        $sender->sendMessage(TextFormat::GREEN . "$args[1]'s nick has been changed");
                                    }
                                }
                                $this->getAPI()->setNick($player, $nickname, true);
                            }
                        }
                    }
                    break;
            }
        }
        return true;
    }
}
