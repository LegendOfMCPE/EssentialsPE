<?php
namespace EssentialsPE\Events;


use EssentialsPE\Loader;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PlayerNickChangeEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /** @var Player  */
    protected $player;
    /** @var  string */
    protected   $new_nick;
    /** @var  string */
    protected   $old_nick;
    /** @var bool|mixed  */
    protected $nametag;

    /**
     * @param Loader $plugin
     * @param Player $player
     * @param string $new_nick
     * @param mixed $nametag
     */
    public function __construct(Loader $plugin, Player $player, $new_nick, $nametag = false){
        parent::__construct($plugin);
        $this->player = $player;
        $this->new_nick = $new_nick;
        $this->old_nick = $player->getDisplayName();
        if($nametag === false){ $this->nametag = $new_nick; }else{ $this->nametag = $nametag; }
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
     * Return the new nick to be set
     *
     * @return string
     */
    public function getNewNick(){
        return $this->new_nick;
    }

    /**
     * Tell the actual nick of the player
     *
     * @return string
     */
    public function getOldNick(){
        return $this->old_nick;
    }

    /**
     * Change the nick to be set
     *
     * @param string $nick
     */
    public function setNick($nick){
        $this->new_nick = $nick;
    }

    /**
     * Return the NameTag to be set
     * Usually it's the same has the new nick, but plugins can use it to modify the NameTag too
     *
     * @return bool|string
     */
    public function getNameTag(){
        return $this->nametag;
    }

    /**
     * Change the NameTag to be set
     *
     * @param string $nametag
     */
    public function setNameTag($nametag){
        $this->nametag = $nametag;
    }
}
