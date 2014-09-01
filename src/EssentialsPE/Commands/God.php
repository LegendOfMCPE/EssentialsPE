<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class God extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "god", "Prevent you to take any damage", "/god [player]", ["godmode", "tgm"]);
        $this->setPermission("essentials.god");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Usage: /god <player>");
                    return false;
                }
                $this->getPlugin()->switchGodMode($sender);
                $sender->sendMessage(TextFormat::AQUA . "God mode " . $this->getPlugin()->isGod($sender) ? "enabled!" : "disabled");
                break;
            case 1:
                if(!$sender->hasPermission("essentials.god.other")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $player = $this->getPlugin()->getPlayer($args[0]);
                if($player === false){
                    $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                    return false;
                }
                $this->getPlugin()->switchGodMode($player);
                $sender->sendMessage(TextFormat::AQUA . "God mode " . ($this->getPlugin()->isGod($player) ? "enabled" : "disabled") . "for $args[0]");
                $player->sendMessage(TextFormat::AQUA . "God mode " . $this->getPlugin()->isGod($player) ? "enabled!" : "disabled");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $sender instanceof Player ? $this->getUsage() : "Usage: /god <player>");
                return false;
                break;
        }
        return true;
    }
}
