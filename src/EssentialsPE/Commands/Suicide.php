<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Burn extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "suicide", "suicide the player who execute it", "/suicide");
        $this->setPermission("essentials.command.suicide");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(isset($args[0]){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
        }else{
                    $sender->kill()
                 }   
//should i set the Death massage here ?
        return true;
    }
} 
