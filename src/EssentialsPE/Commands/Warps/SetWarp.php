<?php
namespace EssentialsPE\Commands\Warps;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SetWarp extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "setwarp", "Open a new warp", "/setwarp <name>", ["openwarp", "createwarp", "addwarp"]);
        $this->setPermission("essentials.setwarp");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        switch(count($args)){
            case 1:
                if($this->getPlugin()->warpExist($args[0]) && (!$sender->hasPermission("essentials.warp.override.*") || !$sender->hasPermission("essentials.warp.override.$args[0]"))){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $this->getPlugin()->setWarp($sender, $args[0]);
                $sender->sendMessage(TextFormat::AQUA . "Successfully " . ($this->getPlugin()->warpExist($args[0]) ? "updated" : "created") . " warp $args[0]!");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $this->getUsage());
                return false;
                break;
        }
        return true;
    }
} 