<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class KickAll extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "kickall", "Kick all the players", "/kickall <reason>");
        $this->setPermission("essentials.kickall");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }

        if(count($args) == 0){
            $reason = "Unknown";
        }else{
            $reason = implode(" ", $args);
        }

        foreach($sender->getServer()->getOnlinePlayers() as $p){
            if($p != $sender){
                $p->kick($reason);
            }
        }
        $sender->sendMessage(TextFormat::AQUA . "Kicked all the players!");
        return true;
    }
}
