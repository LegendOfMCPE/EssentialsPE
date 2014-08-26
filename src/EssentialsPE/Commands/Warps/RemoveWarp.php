<?php
namespace EssentialsPE\Commands\Warps;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;

class RemoveWarp extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "removewarp", "Close a warp", "/removewarp <name>", ["closewarp", "removewarp", "rmwarp", "delwarp"]);
        $this->setPermission("essentials.warp.remove");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        //TODO
        return true;
    }
} 