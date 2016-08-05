<?php
namespace EssentialsPE\BaseFiles;

use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\level\Location;
use pocketmine\Player;
use pocketmine\utils\Config;

class BaseSession{
    /** @var BaseAPI */
    private $api;
    /** @var Player */
    private $player;
    /** @var Config */
    private $config;
    /** @var array */
    public static $defaults = [
        "isAFK" => false,
        "kickAFK" => null,
        "lastMovement" => null,
        "lastPosition" => null,
        "isGod" => false,
        "homes" => [],
        "quickReply" => false,
        "isMuted" => false,
        "mutedUntil" => null,
        "nick" => null,
        "ptCommands" => false,
        "ptChatMacros" => false,
        "isPvPEnabled" => true,
        "requestTo" => false,
        "requestToAction" => false,
        "requestToTask" => null,
        "latestRequestFrom" => null,
        "requestsFrom" => [],
        "isUnlimitedEnabled" => false,
        "isVanished" => false,
        "noPacket" => false
    ];
    /** @var array */
    public static $configDefaults = [
        "isAFK" => false,
        "isGod" => false,
        "homes" => [],
        "isMuted" => false,
        "mutedUntil" => null,
        "nick" => null,
        "ptCommands" => false,
        "ptChatMacros" => false,
        "isPvPEnabled" => true,
        "isUnlimitedEnabled" => false,
        "isVanished" => false
    ];

    /**
     * @param BaseAPI $api
     * @param Player $player
     * @param Config $config
     * @param array $values
     */
    public function __construct(BaseAPI $api, Player $player, Config $config, array $values){
        $this->api = $api;
        $this->player = $player;
        $this->config = $config;
        self::$defaults["lastMovement"] = !$player->hasPermission("essentials.afk.preventauto") ? time() : null;
        foreach($values as $k => $v){
            $this->{$k} = $v;
        }
        $this->loadHomes();
    }

    private function saveSession(){
        $values = [];
        foreach(self::$configDefaults as $k => $v){
            switch($k){
                case "mutedUntil":
                    $v = $this->{$k} instanceof \DateTime ? $this->{$k}->getTimestamp() : null;
                    break;
                case "homes":
                    $v = $this->encodeHomes();
                    break;
                default:
                    $v = $this->{$k};
                    break;
            }
            $values[$k] = $v;
        }
        $this->config->setAll($values);
        $this->config->save(true);
    }

    public function onClose(){
        $this->saveSession();

        // Let's revert some things to their original state...
        $this->setNick(null);
        $this->getAPI()->removeTPRequest($this->getPlayer());
        /*if($this->isVanished()){
            $this->getAPI()->setVanish($this->getPlayer(), false, $this->noPacket());
        }*/
    }

    /**
     * @return Loader
     */
    public final function getPlugin(): Loader{
        return $this->getAPI()->getEssentialsPEPlugin();
    }

    /**
     * @return BaseAPI
     */
    public final function getAPI(): BaseAPI{
        return $this->api;
    }

    /**
     * @return Player
     */
    public final function getPlayer(): Player{
        return $this->player->getPlayer();
    }

    /**
     *            ______ _  __
     *      /\   |  ____| |/ /
     *     /  \  | |__  | ' /
     *    / /\ \ |  __| |  <
     *   / ____ \| |    | . \
     *  /_/    \_|_|    |_|\_\
     */

    /** @var bool */
    private $isAFK = false;
    /** @var int|null */
    private $kickAFK = null;
    /** @var int|null */
    private $lastMovement = null;

    /**
     * @return bool
     */
    public function isAFK(): bool{
        return $this->isAFK;
    }

    /**
     * @param bool $mode
     * @return bool
     */
    public function setAFK(bool $mode): bool{
        $this->isAFK = $mode;
        return true;
    }

    /**
     * @return bool|int
     */
    public function getAFKKickTaskID(){
        if(!$this->isAFK()){
            return false;
        }
        return $this->kickAFK;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function setAFKKickTaskID(int $id): bool{
        $this->kickAFK = $id;
        return true;
    }

    public function removeAFKKickTaskID(){
        $this->kickAFK = null;
    }

    /**
     * @return int|null
     */
    public function getLastMovement(){
        return $this->lastMovement;
    }

    /**
     * @param int $time
     */
    public function setLastMovement(int $time){
        $this->lastMovement = $time;
    }

    /**  ____             _
     *  |  _ \           | |
     *  | |_) | __ _  ___| | __
     *  |  _ < / _` |/ __| |/ /
     *  | |_) | (_| | (__|   <
     *  |____/ \__,_|\___|_|\_\
     */

    /** @var null */
    private $lastLocation = null;

    /**
     * @return bool|Location
     */
    public function getLastPosition(){
        if(!$this->lastLocation instanceof Location){
            return false;
        }
        return $this->lastLocation;
    }

    /**
     * @param Location $pos
     */
    public function setLastPosition(Location $pos){
        $this->lastLocation = $pos;
    }

    public function removeLastPosition(){
        $this->lastLocation = null;
    }

    /**   _____            _                     _   _
     *   / ____|          | |                   | | (_)
     *  | |  __  ___  ___ | |     ___   ___ __ _| |_ _  ___  _ __
     *  | | |_ |/ _ \/ _ \| |    / _ \ / __/ _` | __| |/ _ \| '_ \
     *  | |__| |  __| (_) | |___| (_) | (_| (_| | |_| | (_) | | | |
     *   \_____|\___|\___/|______\___/ \___\__,_|\__|_|\___/|_| |_|
     */

    /** @var null|string */
    private $geoLocation = null;

    /**
     * @return null|string
     */
    public function getGeoLocation(){
        return $this->geoLocation;
    }

    /**
     * @param string $location
     */
    public function setGeoLocation($location){
        $this->geoLocation = $location;
    }

    /**   _____           _
     *   / ____|         | |
     *  | |  __  ___   __| |
     *  | | |_ |/ _ \ / _` |
     *  | |__| | (_) | (_| |
     *   \_____|\___/ \__,_|
     */

    /** @var bool */
    private $isGod = false;

    /**
     * @return bool
     */
    public function isGod(): bool{
        return $this->isGod;
    }

    /**
     * @param bool $mode
     * @return bool
     */
    public function setGod(bool $mode): bool{
        $this->isGod = $mode;
        return true;
    }

    /**  _    _
     *  | |  | |
     *  | |__| | ___  _ __ ___   ___ ___
     *  |  __  |/ _ \| '_ ` _ \ / _ / __|
     *  | |  | | (_) | | | | | |  __\__ \
     *  |_|  |_|\___/|_| |_| |_|\___|___/
     */

    /** @var array */
    private $homes = [];

    private function loadHomes(){
        $homes = [];
        foreach($this->homes as $name => $values){
            if(is_array($values) && count($values) > 1){
                if($this->getPlugin()->getServer()->isLevelGenerated($values[3])){
                    if(!$this->getPlugin()->getServer()->isLevelLoaded($values[3])){
                        $this->getPlugin()->getServer()->loadLevel($values[3]);
                    }
                    $homes[$name] = new BaseLocation($name, $values[0], $values[1], $values[2], $this->getPlugin()->getServer()->getLevelByName($values[3]), $values[4], $values[5]);
                }
            }
        }
        $this->homes = $homes;
    }

    private function encodeHomes(){
        $homes = [];
        foreach($this->homes as $name => $object){
            if($object instanceof BaseLocation){
                $homes[$name] = [$object->getX(), $object->getY(), $object->getZ(), $object->getLevel()->getName(), $object->getYaw(), $object->getPitch()];
            }
        }
        return $homes;
    }

    /**
     * @param string $home
     * @return bool
     */
    public function homeExists(string $home): bool{
        return $this->getAPI()->validateName($home) && isset($this->homes[$home]) && $this->homes[$home] instanceof BaseLocation;
    }

    /**
     * @param string $home
     * @return bool|BaseLocation
     */
    public function getHome(string $home){
        if(!$this->homeExists($home)){
            return false;
        }
        return $this->homes[$home];
    }

    /**
     * @param string $home
     * @param Location $pos
     * @return bool
     */
    public function setHome(string $home, Location $pos): bool{
        if(!$this->getAPI()->validateName($home, false)){
            return false;
        }
        $this->homes[$home] = $pos instanceof BaseLocation ? $pos : BaseLocation::fromPosition($home, $pos);
        return true;
    }

    /**
     * @param string $home
     * @return bool
     */
    public function removeHome(string $home): bool{
        if(!$this->homeExists($home)){
            return false;
        }
        unset($this->homes[$home]);
        return true;
    }

    /**
     * @param bool $inArray
     * @return array|bool|string
     */
    public function homesList(bool $inArray = false){
        $list = array_keys($this->homes);
        if(count($list) < 1){
            return false;
        }
        if(!$inArray){
            return implode(", ", $list);
        }
        return $list;
    }

    /**  __  __
     *  |  \/  |
     *  | \  / |___  __ _
     *  | |\/| / __|/ _` |
     *  | |  | \__ | (_| |
     *  |_|  |_|___/\__, |
     *               __/ |
     *              |___/
     */

    /** @var bool|string */
    private $quickReply = false;

    /**
     * @return bool|string
     */
    public function getQuickReply(){
        return $this->quickReply;
    }

    /**
     * @param CommandSender $sender
     */
    public function setQuickReply(CommandSender $sender){
        $this->quickReply = $sender->getName();
    }

    public function removeQuickReply(){
        $this->quickReply = false;
    }

    /**  __  __       _
     *  |  \/  |     | |
     *  | \  / |_   _| |_ ___
     *  | |\/| | | | | __/ _ \
     *  | |  | | |_| | ||  __/
     *  |_|  |_|\__,_|\__\___|
     */

    /** @var bool */
    private $isMuted = false;
    /** @var \DateTime|null */
    private $mutedUntil = null;

    /**
     * @return bool
     */
    public function isMuted(): bool{
        return $this->isMuted;
    }

    /**
     * @return \DateTime|null
     */
    public function getMutedUntil(){
        return $this->mutedUntil;
    }

    /**
     * @param bool $state
     * @param \DateTime|null $expires
     */
    public function setMuted(bool $state, \DateTime $expires = null){
        $this->isMuted = $state;
        $this->mutedUntil = $expires;
    }

    /**  _   _ _      _
     *  | \ | (_)    | |
     *  |  \| |_  ___| | _____
     *  | . ` | |/ __| |/ / __|
     *  | |\  | | (__|   <\__ \
     *  |_| \_|_|\___|_|\_|___/
     */

    /** @var null|string */
    private $nick = null;

    /**
     * @return null|string
     */
    public function getNick(){
        return $this->nick;
    }

    /**
     * @param null|string $nick
     */
    public function setNick($nick){
        $this->nick = $nick;
        $this->getPlayer()->setDisplayName($nick ?? $this->getPlayer()->getName());
        $this->getPlayer()->setNameTag($nick ?? $this->getPlayer()->getName());
    }

    /**  _____                    _______          _
     *  |  __ \                  |__   __|        | |
     *  | |__) _____      _____ _ __| | ___   ___ | |
     *  |  ___/ _ \ \ /\ / / _ | '__| |/ _ \ / _ \| |
     *  | |  | (_) \ V  V |  __| |  | | (_) | (_) | |
     *  |_|   \___/ \_/\_/ \___|_|  |_|\___/ \___/|_|
     */

    /** @var bool|array */
    private $ptCommands = false;
    /** @var bool|array */
    private $ptChatMacro = false;

    /**
     * @return bool
     */
    public function isPowerToolEnabled(): bool{
        if(!$this->ptCommands && !$this->ptChatMacro){
            return false;
        }
        return true;
    }

    /**
     * @param int $itemId
     * @param string $command
     * @return bool
     */
    public function setPowerToolItemCommand(int $itemId, string $command): bool{
        if($itemId < 1){
            return false;
        }
        if(!is_array($this->ptCommands) || !isset($this->ptCommands[$itemId]) || !is_array($this->ptCommands[$itemId])){
            $this->ptCommands[$itemId] = $command;
        }else{
            $this->ptCommands[$itemId][] = $command;
        }
        return true;
    }

    /**
     * @param int $itemId
     * @return bool|string
     */
    public function getPowerToolItemCommand(int $itemId){
        if($itemId < 1 && !isset($this->ptCommands[$itemId]) || isset($this->ptCommands[$itemId]) && is_array($this->ptCommands[$itemId])){
            return false;
        }elseif($this->ptCommands[$itemId] === null){
            unset($this->ptCommands[$itemId]);
            return false;
        }
        return isset($this->ptCommands[$itemId]) ? $this->ptCommands[$itemId] : null;
    }

    /**
     * @param int $itemId
     * @param array $commands
     * @return bool
     */
    public function setPowerToolItemCommands(int $itemId, array $commands): bool{
        if($itemId < 1 || count($commands) < 1){
            return false;
        }
        $this->ptCommands[$itemId] = $commands;
        return true;
    }

    /**
     * @param int $itemId
     * @return bool
     */
    public function getPowerToolItemCommands(int $itemId): bool{
        if($itemId < 1 || !is_array($this->ptCommands) || !isset($this->ptCommands[$itemId]) || !is_array($this->ptCommands[$itemId])){
            return false;
        }elseif($this->ptCommands[$itemId] === null){
            unset($this->ptCommands[$itemId]);
            return false;
        }
        return $this->ptCommands[$itemId];
    }

    /**
     * @param int $itemId
     * @param string $command
     */
    public function removePowerToolItemCommand(int $itemId, string $command){
        $commands = $this->getPowerToolItemCommands($itemId);
        if(is_array($commands)){
            foreach($commands as $c){
                if(stripos(strtolower($c), strtolower($command)) !== false){
                    unset($c);
                }
            }
        }
    }

    /**
     * @param int $itemId
     * @param string $chat_message
     * @return bool
     */
    public function setPowerToolItemChatMacro(int $itemId, string $chat_message): bool{
        if($itemId < 1){
            return false;
        }
        $chat_message = str_replace("\\n", "\n", $chat_message);
        $this->ptChatMacro[$itemId] = $chat_message;
        return true;
    }

    /**
     * @param int $itemId
     * @return bool
     */
    public function getPowerToolItemChatMacro(int $itemId): bool{
        if($itemId < 1 || !isset($this->ptChatMacro[$itemId])){
            return false;
        }
        return $this->ptChatMacro[$itemId];
    }

    /**
     * @param int $itemId
     */
    public function disablePowerToolItem(int $itemId){
        unset($this->ptCommands[$itemId]);
        unset($this->ptChatMacro[$itemId]);
    }

    public function disablePowerTool(){
        $this->ptCommands = false;
        $this->ptChatMacro = false;
    }

    /**  _____        _____
     *  |  __ \      |  __ \
     *  | |__) __   _| |__) |
     *  |  ___/\ \ / |  ___/
     *  | |     \ V /| |
     *  |_|      \_/ |_|
     */

    /** @var bool */
    private $isPvPEnabled = true;

    /**
     * @return bool
     */
    public function isPVPEnabled(): bool{
        return $this->isPvPEnabled;
    }

    /**
     * @param bool $mode
     * @return bool
     */
    public function setPvP(bool $mode): bool{
        $this->isPvPEnabled = $mode;
        return true;
    }

    /**  _______ _____  _____                           _
     *  |__   __|  __ \|  __ \                         | |
     *     | |  | |__) | |__) |___  __ _ _   _  ___ ___| |_ ___
     *     | |  |  ___/|  _  // _ \/ _` | | | |/ _ / __| __/ __|
     *     | |  | |    | | \ |  __| (_| | |_| |  __\__ | |_\__ \
     *     |_|  |_|    |_|  \_\___|\__, |\__,_|\___|___/\__|___/
     *                                | |
     *                                |_|
     */

    //Request to:
    /** @var bool|string */
    private $requestTo = false;
    /** @var bool|string */
    private $requestToAction = false;
    /** @var null|int */
    private $requestToTask = null;

    /**
     * @return array|bool
     */
    public function madeARequest(){
        return ($this->requestTo !== false ? [$this->requestTo, $this->requestToAction] : false);
    }

    /**
     * @param string $target
     * @return bool
     */
    public function madeARequestTo(string $target): bool{
        return $this->requestTo === $target;
    }

    /**
     * @param string $target
     * @param string $action
     */
    public function requestTP(string $target, string $action){
        $this->requestTo = $target;
        $this->requestToAction = $action;
    }

    public function cancelTPRequest(){
        $this->requestTo = false;
        $this->requestToAction = false;
    }

    /**
     * @return bool|int
     */
    public function getRequestToTaskID(){
        return ($this->requestToTask !== null ? $this->requestToTask : false);
    }

    /**
     * @param int $taskId
     * @return bool
     */
    public function setRequestToTaskID(int $taskId): bool{
        $this->requestToTask = $taskId;
        return true;
    }

    public function removeRequestToTaskID(){
        $this->requestToTask = null;
    }

    //Requests from:
    /** @var null|string */
    private $latestRequestFrom = null;
    /** @var array */
    private $requestsFrom = [];
    /** This is how it works per player:
    *
    * "iksaku" => "tpto"  <--- Type of request
    *    ^^^
    * Requester Name
    */

    /**
     * @return array|bool
     */
    public function hasARequest(){
        return (count($this->requestsFrom) > 0 ? $this->requestsFrom : false);
    }

    /**
     * @param string $requester
     * @return bool|string
     */
    public function hasARequestFrom(string $requester){
        return (isset($this->requestsFrom[$requester]) ? $this->requestsFrom[$requester] : false);
    }

    /**
     * @return bool|string
     */
    public function getLatestRequestFrom(){
        return ($this->latestRequestFrom !== null ? $this->latestRequestFrom : false);
    }

    /**
     * @param string $requester
     * @param string $action
     */
    public function receiveRequest(string $requester, string $action){
        $this->latestRequestFrom = $requester;
        $this->requestsFrom[$requester] = $action;
    }

    /**
     * @param string $requester
     */
    public function removeRequestFrom(string $requester){
        unset($this->requestsFrom[$requester]);
        if($this->getLatestRequestFrom() === $requester){
            $this->latestRequestFrom = null;
        }
    }

    /**  _    _       _ _           _ _           _   _____ _
     *  | |  | |     | (_)         (_| |         | | |_   _| |
     *  | |  | |_ __ | |_ _ __ ___  _| |_ ___  __| |   | | | |_ ___ _ __ ___  ___
     *  | |  | | '_ \| | | '_ ` _ \| | __/ _ \/ _` |   | | | __/ _ | '_ ` _ \/ __|
     *  | |__| | | | | | | | | | | | | ||  __| (_| |  _| |_| ||  __| | | | | \__ \
     *   \____/|_| |_|_|_|_| |_| |_|_|\__\___|\__,_| |_____|\__\___|_| |_| |_|___/
     */

    /** @var bool */
    private $isUnlimitedEnabled = false;

    /**
     * @return bool
     */
    public function isUnlimitedEnabled(): bool{
        return $this->isUnlimitedEnabled;
    }

    /**
     * @param bool $mode
     * @return bool
     */
    public function setUnlimited(bool $mode): bool{
        $this->isUnlimitedEnabled = $mode;
        return true;
    }

    /** __      __         _     _
     *  \ \    / /        (_)   | |
     *   \ \  / __ _ _ __  _ ___| |__
     *    \ \/ / _` | '_ \| / __| '_ \
     *     \  | (_| | | | | \__ | | | |
     *      \/ \__,_|_| |_|_|___|_| |_|
     */

    /** @var bool */
    private $isVanished = false;

    /**
     * If set to true, we will use Player packets instead of Effect ones
     *
     * @var bool
     */
    private $noPacket = false;

    /**
     * @return bool
     */
    public function isVanished(): bool{
        return $this->isVanished;
    }

    /**
     * @param bool $mode
     * @param bool $noPacket
     * @return bool
     */
    public function setVanish(bool $mode, bool $noPacket): bool{
        $this->isVanished = $mode;
        $this->noPacket = $noPacket;
        return true;
    }

    /**
     * @return bool
     */
    public function noPacket(): bool{
        return $this->noPacket;
    }
}