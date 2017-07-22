<?php
namespace EssentialsPE\Commands\Override;

use EssentialsPE\BaseFiles\BaseAPI;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Kill extends BaseOverrideCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "kill", "Kill other people", "[player]");
        $this->setPermission("essentials.kill.use");
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
        if(!$sender instanceof Player && count($args) !== 1){
            $this->sendUsage($sender, $alias);
            return false;
        }
        $player = $sender;
        if(isset($args[0])){
            if(!$sender->hasPermission("essentials.kill.other")){
                $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                return false;
            }
            if(!($player = $this->getAPI()->getPlayer($args[0])) instanceof Player){
                $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                return false;
            }
        }
        if($this->getAPI()->isGod($player)){
            $sender->sendMessage(TextFormat::RED . $args[0] . " can't be killed!");
            return false;
        }
        $sender->getServer()->getPluginManager()->callEvent($ev = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_SUICIDE, $player->getHealth()));
        if($ev->isCancelled()){
            return true;
        }

        $player->setLastDamageCause($ev);
        $player->setHealth(0);
        $player->sendMessage("Ouch. That looks like it hurt.");
        return true;
    }
} 