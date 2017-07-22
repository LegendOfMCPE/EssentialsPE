<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Feed extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "feed", "Feed yourself or other players", "[player]");
        $this->setPermission("essentials.feed.use");
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if(!$this->testPermission($sender)){
            return false;
        }
        if((!isset($args[0]) && !$sender instanceof Player) || count($args) > 1){
            $this->sendUsage($sender, $alias);
            return false;
        }
        $player = $sender;
        if(isset($args[0]) && !($player = $this->getAPI()->getPlayer($args[0]))){
            $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
            return false;
        }
	    if($player->getName() !== $sender->getName() && !$sender->hasPermission("essentials.feed.other")) {
		    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
		    return false;
	    }
        $player->setFood(20);
        $player->getLevel()->addParticle(new HappyVillagerParticle($player->add(0, 2)));
        $player->sendMessage(TextFormat::GREEN . "You have been fed!");
        if($player !== $sender){
            $sender->sendMessage(TextFormat::GREEN . $player->getDisplayName() . " has been fed!");
        }
        return true;
    }
}
