<?php
namespace EssentialsPE\Commands\Warps;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RemoveWarp extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "delwarp", "Close a warp", "/removewarp <name>", ["closewarp", "removewarp", "rmwarp"]);
        $this->setPermission("essentials.delwarp");
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
                if(!$this->getPlugin()->warpExist($args[0])){
                    $sender->sendMessage(TextFormat::RED . "[Error] Warp not found");
                    return false;
                }
                $this->getPlugin()->removeWarp($args[0]);
                $sender->sendMessage(TextFormat::RED . "Successfully removed warp $args[0]");
                break;
            default:
                $sender->sendMessage(TextFormat::RED . $this->getUsage());
                return false;
                break;
        }
        return true;
    }
} 