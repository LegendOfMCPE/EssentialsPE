<?php
namespace EssentialsPE\Events;

use EssentialsPE\Loader;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerAFKModeChangeEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /** @var \pocketmine\Player  */
    protected $player;
    /** @var bool  */
    protected $isAFK;
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
        $this->isAFK = $plugin->isAFK($player);
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
     * Tell if the player is already AFK or not
     *
     * @return bool
     */
    public function isAFK(){
        return $this->isAFK;
    }

    /**
     * Tell the mode will to be set
     *
     * @return bool
     */
    public function getAFKMode(){
        return $this->mode;
    }

    /**
     * Change the mode to be set
     * false = Player will not be AFK
     * true = Player will be AFK
     *
     * @param bool $mode
     */
    public function setAFKMode($mode){
        if(is_bool($mode)){
            $this->mode = $mode;
        }
    }
} 