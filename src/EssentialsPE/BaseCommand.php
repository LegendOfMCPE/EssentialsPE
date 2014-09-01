<?php
namespace EssentialsPE;

use pocketmine\command\Command;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\event\Listener;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand, Listener{
    /** @var \EssentialsPE\Loader  */
    private $api;

    public function __construct(Loader $plugin, $name, $description = "", $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->api = $plugin;
    }

    public function getPlugin(){
        return $this->api;
    }
}
