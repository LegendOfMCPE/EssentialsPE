<?php
namespace EssentialsPE\Events;

use EssentialsPE\Loader;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerUnlimitedModeChangeEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /**
     * @var \pocketmine\Player
     */
    protected $player;
    /** @var bool  */
    protected $isEnabled;
    /** @var  bool */
    protected $mode;

    /**
     * @param Loader $plugin
     * @param Player $player
     * @param bool $mode
     */
    public function __construct(Loader $plugin, Player $player, $mode){
        parent::__construct($plugin);
        $this->player = $player;
        $this->isEnabled = $plugin->isUnlimitedEnabled($player);
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
     * Tell is the player already have the Unlimited Placing of items enabled
     *
     * @return bool
     */
    public function isUnlimitedEnabled(){
        return $this->isEnabled;
    }

    /**
     * Tell the mode to be set
     *
     * @return bool
     */
    public function getUnlimitedMode(){
        return $this->mode;
    }

    /**
     * Change the mode to be set
     * false = Unlimited will be disabled
     * true = Unlimited will be enabled
     *
     * @param bool $mode
     */
    public function setUnlimitedMode($mode){
        if(is_bool($mode)){
            $this->mode = $mode;
        }
    }
} 