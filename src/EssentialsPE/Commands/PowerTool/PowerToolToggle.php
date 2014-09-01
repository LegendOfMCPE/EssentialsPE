<?php
namespace EssentialsPE\Commands\PowerTool;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PowerToolToggle extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "powertooltoggle", "Disable PowerTool from all the items", "/powertooltoggle", ["ptt", "pttoggle"]);
        $this->setPermission("essentials.powertooltoggle");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }
        if(count($args) > 0){
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return false;
        }
        $this->getPlugin()->disablePowerTool($sender);
        $sender->sendMessage(TextFormat::YELLOW . "PowerTool disabled from all the items!");
        return true;
    }
} 