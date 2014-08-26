<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RealName extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "realname", "Check the realname of a player", "/realname <player>");
        $this->setPermission("essentials.realname");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }

        if(count($args) != 1){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
        }else{
            $player = $this->getAPI()->getPlayer($args[0]);
            if($player === false){
                $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
            }else{
                if(substr($args[0], -1, 1) != "s"){
                    $sender->sendMessage(TextFormat::YELLOW . "$args[0]'s real name is: " . TextFormat::RESET . $player->getName());
                }else{
                    $sender->sendMessage(TextFormat::YELLOW . "$args[0]' real name is: " . TextFormat::RESET . $player->getName());
                }
            }
        }
        return true;
    }
}
