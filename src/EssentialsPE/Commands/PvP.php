<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PvP extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "pvp", "Toggle PvP on/off", "<on|true|enable|off|false|disable>", false);
        $this->setPermission("essentials.pvp");
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
        if(!$sender instanceof Player || count($args) !== 1 || !((($s = strtolower($args[0])) === "on" || (bool) $s || $s === "enable") || ($s === "off" || !((bool) $s)) || $s === "disable")){
            $this->sendUsage($sender, $alias);
            return false;
        }
        $this->getAPI()->setPvP($sender, $s);
        $sender->sendMessage(TextFormat::GREEN . "PvP mode " . ($s ? "enabled" : "disabled"));
        return true;
    }
}
