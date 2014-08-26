<?php
namespace EssentialsPE\Events;

use EssentialsPE\Loader;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerPvPModeChangeEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /** @var \pocketmine\Player  */
    protected $player;
    /** @var bool  */
    protected $isEnabled;
    /** @var bool  */
    protected $mode;

    /**
     * @param Loader $plugin
     * @param Player $player
     * @param bool $mode
     */
    public function __construct(Loader $plugin, Player $player, $mode){
        parent::__construct($plugin);
        $this->player = $player;
        $this->isEnabled = $plugin->isPvPEnabled($player);
        $this->mode = $mode;
    }

    /**
     * Return the player to be used
     *
     * @return Player
     */
    public function getPlayer(){
        return $this->player;
    }

    /**
     * Tell if the player already have PvP enabled
     *
     * @return bool
     */
    public function isPvPEnabled(){
        return $this->isEnabled;
    }

    /**
     * Tell the mode to be set
     *
     * @return bool
     */
    public function getPvPMode(){
        return $this->mode;
    }

    /**
     * Change the PVP mode
     * false = PvP mode will be disabled for the player
     * true = PvP mode will be enabled for the player
     *
     * @param bool $mode
     */
    public function setPvPMode($mode){
        if(is_bool($mode)){
            $this->mode = $mode;
        }
    }
} 