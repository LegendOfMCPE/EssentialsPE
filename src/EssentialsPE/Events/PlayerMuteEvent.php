<?php
namespace EssentialsPE\Events;

use EssentialsPE\Loader;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerMuteEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /** @var \pocketmine\Player  */
    protected $player;
    /** @var  bool */
    protected $isMuted;
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
        $this->isMuted = $plugin->isMuted($player);
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
     * Tell is the player is already muted
     *
     * @return bool
     */
    public function isMuted(){
        return $this->isMuted;
    }

    /**
     * Tell if the player will be muted or not
     *
     * @return bool
     */
    public function willMute(){
        return $this->mode;
    }

    /**
     * Change the Mute mode to be set
     * false = Player will not be muted
     * true = Player will be muted
     *
     * @param bool $mode
     */
    public function setMuted($mode){
        if(is_bool($mode)){
            $this->mode = $mode;
        }
    }
} 