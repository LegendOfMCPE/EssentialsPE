<?php
namespace EssentialsPE\Commands\Warps;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;

class SetWarp extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "setwarp", "Open a new warp", "/setwarp <name>", ["openwarp", "createwarp", "addwarp"]);
        $this->setPermission("essentials.command.warp.set");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        //TODO Save the data
        if(! isset($args[0])){
            return false;
        }
        $warpName = $args[0];
        //Save data
        return true;
    }
} 
