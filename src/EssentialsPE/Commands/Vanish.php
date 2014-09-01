<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Vanish extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "vanish", "Hide from other players!", "/vanish [player]", ["v"]);
        $this->setPermission("essentials.vanish");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /vanish <player>");
                    return false;
                }
                $this->getAPI()->switchVanish($sender);
                $sender->sendMessage(TextFormat::GRAY . "You're now " . ($this->getAPI()->isVanished($sender) ? "vanished!" : "visible!"));
                break;
            case 1:
                $player = $this->getAPI()->getPlayer($args[0]);
                if($player == false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found.");
                    return false;
                }
                $this->getAPI()->switchVanish($player);
                $sender->sendMessage(TextFormat::GRAY . "$args[0] is now " . ($this->getAPI()->isVanished($player) ? "vanished!" : "visible!"));
                $player->sendMessage(TextFormat::GRAY . "You're now " . ($this->getAPI()->isVanished($sender) ? "vanished!" : "visible!"));
                break;
            default:
                $sender->sendMessage(TextFormat::RED . ($sender instanceof Player ? "" : "Usage: ") . $this->getUsage());
                return false;
                break;
        }
        return true;
    }
}
