<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class KickAll extends BaseCommand{
    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "kickall", "Kick all the players", "/kickall <reason>");
        $this->setPermission("essentials.kickall");
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
        if(count($this->getServer()->getOnlinePlayers()) < 1){
	    $sender->sendMessage(TextFormat::RED . "There is no players on the server!");
            return true;
	}
        if(count($args) < 1){
            $reason = "Unknown";
        }else{
            $reason = implode(" ", $args);
        }
        foreach($sender->getServer()->getOnlinePlayers() as $p){
            if($p != $sender){
                $p->kick($reason, false);
            }
        }
        $sender->sendMessage(TextFormat::AQUA . "Kicked all the players!");
        return true;
    }
}
