<?php
namespace EssentialsPE\Events;

use EssentialsPE\Loader;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerVanishEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /** @var \pocketmine\Player  */
    protected $player;
    /** @var bool  */
    protected $isVanished;
    /** @var  bool */
    protected $willVanish;

    /**
     * @param Loader $plugin
     * @param Player $player
     * @param bool $willVanish
     */
    public function __construct(Loader $plugin, Player $player, $willVanish){
        parent::__construct($plugin);
        $this->player = $player;
        $this->isVanished = $plugin->isVanished($player);
        $this->willVanish = $willVanish;
    }

    /**
     * Return the player that will be vanished/shown
     *
     * @return Player
     */
    public function getPlayer(){
        return $this->player;
    }

    /**
     * Tell if the player is already vanished or not
     *
     * @return bool
     */
    public function isVanished(){
        return $this->isVanished;
    }

    /**
     * Tell if the player will be vanished or showed
     * false = Player will be showed
     * true = Player will be vanished
     *
     * @return bool
     */
    public function willVanish(){
        return $this->willVanish;
    }

    /**
     * Change the vanish mode that will be set
     * false = Player will be shown
     * true = Player will be vanished
     *
     * @param bool $value
     */
    public function setVanished($value){
        if(is_bool($value)){
            $this->willVanish = $value;
        }
    }
} 