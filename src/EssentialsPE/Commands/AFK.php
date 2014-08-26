<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AFK extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "afk", "Toggle the \"Away From the Keyboard\" status", "/afk [player]", ["away"]);
        $this->setPermission("essentials.afk.use");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) > 1){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "Usage: /afk <player>");
            }else{
                $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
            }
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /afk <player>");
                    return false;
                }
                $this->getAPI()->switchAFKMode($sender);
                if(!$this->getAPI()->isAFK($sender)){
                    $sender->sendMessage(TextFormat::YELLOW . "You're no longer AFK");
                    $this->broadcastAFKStatus($sender, "is no longer AFK");
                }else{
                    $sender->sendMessage(TextFormat::YELLOW . "You're now AFK");
                    $this->broadcastAFKStatus($sender, "is now AFK");
                }
                return true;
                break;
            case 1:
                if(!$sender->hasPermission("essentials.afk.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getAPI()->getPlayer($args[0]);
                if(!$player instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }
                $this->getAPI()->switchAFKMode($player);
                if(!$this->getAPI()->isAFK($player)){
                    $player->sendMessage(TextFormat::YELLOW . "You're no longer AFK");
                    $this->broadcastAFKStatus($player, "is no longer AFK");
                }else{
                    $player->sendMessage(TextFormat::YELLOW . "You're now AFK");
                    $this->broadcastAFKStatus($player, "is now AFK");
                }
                return true;
                break;
        }
        return true;
    }

    private function broadcastAFKStatus(Player $player, $message){
        $player->getServer()->getLogger()->info(TextFormat::GREEN . $player->getDisplayName() . " " . $message);
        foreach($player->getServer()->getOnlinePlayers() as $p){
            if($p !== $player){
                $p->sendMessage(TextFormat::YELLOW . $player->getDisplayName() . " " . $message);
            }
        }
    }
} 