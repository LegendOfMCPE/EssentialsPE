<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Vanish extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "vanish", "Hide from other players!", "[player]", true, ["v"]);
        $this->setPermission("essentials.vanish.use");
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
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $this->sendUsage($sender, $alias);
                    return false;
                }
                $this->getAPI()->switchVanish($sender);
                $sender->sendMessage(TextFormat::GRAY . "You're now " . ($this->getAPI()->isVanished($sender) ? "vanished!" : "visible!"));
                break;
            case 1:
                if(!$sender->hasPermission("essentials.vanish.other")){
                    $sender->sendMessage($this->getPermissionMessage());
                    return false;
                }
                if(!($player = $this->getAPI()->getPlayer($args[0]))){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }
                $this->getAPI()->switchVanish($player);
                $sender->sendMessage(TextFormat::GRAY .  $player->getDisplayName() . " is now " . ($this->getAPI()->isVanished($player) ? "vanished!" : "visible!"));
                $player->sendMessage(TextFormat::GRAY . "You're now " . ($this->getAPI()->isVanished($player) ? "vanished!" : "visible!"));
                break;
            default:
                $this->sendUsage($sender, $alias);
                return false;
                break;
        }
        return true;
    }
}
