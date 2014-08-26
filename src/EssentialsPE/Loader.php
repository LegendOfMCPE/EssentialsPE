<?php
namespace EssentialsPE;

use EssentialsPE\Commands\AFK;
use EssentialsPE\Commands\Broadcast; //Use API
use EssentialsPE\Commands\Burn; //Use API
use EssentialsPE\Commands\ClearInventory; //Use API
use EssentialsPE\Commands\Compass;
use EssentialsPE\Commands\Essentials;
use EssentialsPE\Commands\Extinguish; //Use API
use EssentialsPE\Commands\GetPos; //Use API
use EssentialsPE\Commands\God; //Use API
use EssentialsPE\Commands\Heal; //Use API
use EssentialsPE\Commands\Item as ItemCommand;
use EssentialsPE\Commands\Jump;
use EssentialsPE\Commands\KickAll;
use EssentialsPE\Commands\More;
use EssentialsPE\Commands\Mute; //Use API
use EssentialsPE\Commands\Nick; //Use API
use EssentialsPE\Commands\PowerTool\PowerTool; //Use API
use EssentialsPE\Commands\PowerTool\PowerToolToggle; //Use API
use EssentialsPE\Commands\PvP; //Use API
use EssentialsPE\Commands\RealName; //Use API
use EssentialsPE\Commands\Repair;
use EssentialsPE\Commands\Seen;
use EssentialsPE\Commands\SetSpawn;
use EssentialsPE\Commands\TempBan;
use EssentialsPE\Commands\Top;
use EssentialsPE\Commands\Unlimited;
use EssentialsPE\Commands\Vanish; //Use API
use EssentialsPE\Commands\Warps\RemoveWarp; //Use API
use EssentialsPE\Commands\Warps\SetWarp; //Use API
use EssentialsPE\Commands\Warps\Warp; //Use API
use EssentialsPE\Events\EventHandler; //Use API
use EssentialsPE\Events\PlayerAFKModeChangeEvent;
use EssentialsPE\Events\PlayerGodModeChangeEvent;
use EssentialsPE\Events\PlayerMuteEvent;
use EssentialsPE\Events\PlayerNickChangeEvent;
use EssentialsPE\Events\PlayerPvPModeChangeEvent;
use EssentialsPE\Events\PlayerUnlimitedModeChangeEvent;
use EssentialsPE\Events\PlayerVanishEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase{
    public $path;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->checkConfig();
	    $this->getLogger()->info(TextFormat::YELLOW . "Loading...");
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
        $this->registerCommands();

        foreach($this->getServer()->getOnlinePlayers() as $p){
            //Nicks
            $this->setNick($p, $this->getNick($p), false);
            //Sessions & Mute
            $this->muteSessionCreate($p);
            $this->createSession($p);
        }
    }

    public function onDisable(){
        foreach($this->getServer()->getOnlinePlayers() as $p){
            //Nicks
            $this->setNick($p, $p->getName(), false);
            //Vanish
            if($this->getSession($p, "vanish") === true){
                foreach($this->getServer()->getOnlinePlayers() as $players){
                    $players->showPlayer($p);
                }
            }
        }
    }

    private function registerCommands(){
        $this->getServer()->getCommandMap()->registerAll("essentialspe", [
            new AFK($this),
            new Broadcast($this),
            new Burn($this),
            new ClearInventory($this),
            new Compass($this),
            new Essentials($this),
            new Extinguish($this),
            new GetPos($this),
            new God($this),
            new Heal($this),
            //new ItemCommand($this), // TODO
            //new Jump($this), // TODO
            //new TempBan($this), //TODO
            new KickAll($this),
            new More($this),
            new Mute($this),
            new Nick($this),
            new PowerTool($this),
            new PowerToolToggle($this),
            new PvP($this),
            new RealName($this),
            new Repair($this),
            new Seen($this),
            new SetSpawn($this),
            new Top($this),
            new Unlimited($this),
            new Vanish($this),

            //Wraps
            //new RemoveWarp($this), // TODO
            //new SetWarp($this), // TODO
            //new Warp($this), // TODO
        ]);
    }

    private function checkConfig(){
        $this->saveDefaultConfig();

        if(!is_bool($this->getConfig()->get("safe-afk"))){
            $this->getConfig()->set("safe-afk", true);
        }

        $this->saveConfig();
        $this->reloadConfig();
    }

    /*
     *  .----------------.  .----------------.  .----------------.
     * | .--------------. || .--------------. || .--------------. |
     * | |      __      | || |   ______     | || |     _____    | |
     * | |     /  \     | || |  |_   __ \   | || |    |_   _|   | |
     * | |    / /\ \    | || |    | |__) |  | || |      | |     | |
     * | |   / ____ \   | || |    |  ___/   | || |      | |     | |
     * | | _/ /    \ \_ | || |   _| |_      | || |     _| |_    | |
     * | ||____|  |____|| || |  |_____|     | || |    |_____|   | |
     * | |              | || |              | || |              | |
     * | '--------------' || '--------------' || '--------------' |
     *  '----------------'  '----------------'  '----------------'
     *
     */

    /**
     * Let you search for a player using his Display name(Nick) or Real name
     *
     * @param $player
     * @return bool|Player
     */
    public function getPlayer($player){
        $player = strtolower($player);
        $r = false;
        foreach($this->getServer()->getOnlinePlayers() as $p){
            if(strtolower($p->getDisplayName()) === $player || strtolower($p->getName()) === $player){
                $r = $p;
                break;
            }
        }
        return $r;
    }

    /**
     * Return a colored message replacing every
     * color code (&a = §a)
     *
     * @param string $message
     * @param null $player
     * @return mixed
     */
    public function colorMessage($message, $player = null){
        $search = ["&0","&1","&2","&3","&4","&5","&6","&7","&8","&9","&a", "&b", "&c", "&d", "&e", "&f", "&k", "&l", "&m", "&n", "&o", "&r"];
        $formats = ["§0", "§1", "§2", "§3", "§4", "§5", "§6", "§7", "§8", "§9", "§a", "§b", "§c", "§d", "§e", "§f", "§k", "§l", "§m", "§n", "§o", "§r"];
        foreach($search as $s){
            $code = substr($s, -1, 1);
            $message = str_replace($s, "§" . $code, $message);
        }
        foreach($formats as $f){
            $code = $code = substr($f, -1, 1);
            $message = str_replace("\\" . $f, "&" . $code, $message);
        }
        if(strpos($message, "§") !== false && ($player instanceof Player) && !$player->hasPermission("essentials.colorchat")){
            $player->sendMessage(TextFormat::RED . "You can't chat using colors!");
            return false;
        }
        return $message;
    }

    /**   _____              _
     *   / ____|            (_)
     *  | (___   ___ ___ ___ _  ___  _ __  ___
     *   \___ \ / _ / __/ __| |/ _ \| '_ \/ __|
     *   ____) |  __\__ \__ | | (_) | | | \__ \
     *  |_____/ \___|___|___|_|\___/|_| |_|___/
     */

    /** @var array  */
    private $sessions = [];
    /** @var array  */
    private $mutes = [];
    /** @var array  */
    private $default = [
        "afk" => false,
        "god" => false,
        "powertool" => [
            "commands" => false,
            "chat-macro" => false,
        ],
        "pvp" => false,
        "unlimited" => false,
        "vanish" => false
    ];

    /**
     * Creates a new Sessions for the specified player
     *
     * @param Player $player
     */
    public function createSession(Player $player){
        $this->sessions[$player->getName()] = $this->default;
    }

    /**
     * Removes a player's session (if active and available)
     *
     * @param Player $player
     */
    public function removeSession(Player $player){
        unset($this->sessions[$player->getName()]);
    }

    /**
     * Modify the value of a session key (See "Mute" for example)
     *
     * @param Player $player
     * @param $key
     * @param $value
     * @return bool
     */
    public function setSession(Player $player, $key, $value){
        if(!(isset($this->sessions[$player->getName()]) || isset($this->sessions[$player->getName()][$key]))){
            return false;
        }
        $this->sessions[$player->getName()][$key] = $value;
        return true;
    }

    /**
     * Return the value of a session key
     *
     * @param Player $player
     * @param $key
     * @return bool
     */
    public function getSession(Player $player, $key){
        if(!(isset($this->sessions[$player->getName()]) || isset($this->sessions[$player->getName()][$key]))){
            return false;
        }
        return $this->sessions[$player->getName()][$key];
    }

    /**
     *            ______ _  __
     *      /\   |  ____| |/ /
     *     /  \  | |__  | ' /
     *    / /\ \ |  __| |  <
     *   / ____ \| |    | . \
     *  /_/    \_|_|    |_|\_\
     */

    /**
     * Change the AFK mode of a player
     *
     * @param Player $player
     * @param bool $state
     * @return bool
     */
    public function setAFKMode(Player $player, $state){
        if(!is_bool($state)){
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerAFKModeChangeEvent($this, $player, $state));
        if($ev->isCancelled()){
            return false;
        }
        $state = $ev->getAFKMode();
        $this->setSession($player, "afk", $state);
        return true;
    }

    /**
     * Automatically switch the AFK mode on/off
     *
     * @param Player $player
     */
    public function switchAFKMode(Player $player){
        if(!$this->isAFK($player)){
            $this->setAFKMode($player, true);
        }else{
            $this->setAFKMode($player, false);
        }
    }

    /**
     * Tell if the player is AFK or not
     *
     * @param Player $player
     * @return bool
     */
    public function isAFK(Player $player){
        return $this->getSession($player, "afk");
    }

    /**   _____           _
     *   / ____|         | |
     *  | |  __  ___   __| |
     *  | | |_ |/ _ \ / _` |
     *  | |__| | (_) | (_| |
     *   \_____|\___/ \__,_|
     */

    /**
     * Set the God Mode on or off
     *
     * @param Player $player
     * @param bool $state
     * @return bool
     */
    public function setGodMode(Player $player, $state){
        if(!is_bool($state)){
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerGodModeChangeEvent($this, $player, $state));
        if($ev->isCancelled()){
            return false;
        }
        $state = $ev->getGodMode();
        $this->setSession($player, "god", $state);
        return true;
    }

    /**
     * Switch God Mode on/off automatically
     *
     * @param Player $player
     */
    public function switchGodMode(Player $player){
        if(!$this->isGod($player)){
            $this->setGodMode($player, true);
        }else{
            $this->setGodMode($player, false);
        }
    }

    /**
     * Tell if a player is in God Mode
     *
     * @param Player $player
     * @return bool
     */
    public function isGod(Player $player){
        return $this->getSession($player, "god");
    }

    /**  _    _
     *  | |  | |
     *  | |__| | ___  _ __ ___   ___
     *  |  __  |/ _ \| '_ ` _ \ / _ \
     *  | |  | | (_) | | | | | |  __/
     *  |_|  |_|\___/|_| |_| |_|\___|
     */

    /**
     * Sets a new home location or modify it if the home exists
     *
     * @param Player $player
     * @param string $home_name
     * @return bool
     */
    public function setHome(Player $player, $home_name){
        $config = new Config($this->getDataFolder() . $player->getName() . ".yml");
        if(!$config->exists($home_name)){
            if(!$player->hasPermission("essentials.home." . ($this->countHomes($player) + 1))){
                $player->sendMessage("You may only have ".$this->countHomes($player)." homes.");
                return true;
            }
            $pos = array();
            $pos["x"] = $player->getX();
            $pos["y"] = $player->getY();
            $pos["z"] = $player->getZ();
            $pos["yaw"] = $player->yaw;
            $pos["pitch"] = $player->pitch;
            $pos["level"] = $player->getLevel()->getName();
            $config->set($home_name, $pos);
        }
        return true;
    }

    /**
     * Teleport to the selected home
     *
     * @param Player $player
     * @param string $home_name
     * @return bool
     */
    public function homeTp(Player $player, $home_name){
        $config = new Config($this->getDataFolder() . strtolower($player->getName()) . ".yml");
        if(!$config->exists(strtolower($home_name))){
            return false;
        }
        $home = $config->get(strtolower($home_name));
        if($player->getLevel()->getName() != $home["level"]){
            $player->setLevel($home["level"]);
        }
        $player->teleport(new Vector3($home["x"], $home["y"], $home["z"]), $home["yaw"], $home["pitch"]);
        return true;
    }

    /**
     * Count the number of homes that a player has
     *
     * @param Player $player
     * @return int
     */
    public function countHomes(Player $player){
        $config = new Config($this->getDataFolder() . $player->getName() . ".yml");
        return count($config->getAll());
    }

    /**  __  __       _
     *  |  \/  |     | |
     *  | \  / |_   _| |_ ___
     *  | |\/| | | | | __/ _ \
     *  | |  | | |_| | ||  __/
     *  |_|  |_|\__,_|\__\___|
     */

    /**
     * Create the mute session for a player
     *
     * The mute session is handled separately of other Sessions because
     * using it separately, players can't be unmuted by leaving and joining again...
     *
     * @param Player $player
     */
    public function muteSessionCreate(Player $player){
        if(!isset($this->mutes[$player->getName()])){
            $this->mutes[$player->getName()] = false;
        }
    }

    /**
     * Set the Mute mode on or off
     *
     * @param Player $player
     * @param bool $state
     * @return bool
     */
    public function setMute(Player $player, $state){
        if(!is_bool($state)){
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerMuteEvent($this, $player, $state));
        if($ev->isCancelled()){
            return false;
        }
        $state = $ev->willMute();
        $this->mutes[$player->getName()] = $state;
        return true;
    }

    /**
     * Switch the Mute mode on/off automatically
     *
     * @param Player $player
     */
    public function switchMute(Player $player){
        if(!$this->isMuted($player)){
            $this->setMute($player, true);
        }else{
            $this->setMute($player, false);
        }
    }

    /**
     * Tell if the is Muted or not
     *
     * @param Player $player
     * @return bool
     */
    public function isMuted(Player $player){
        return $this->mutes[$player->getName()];
    }

    /** _   _ _      _
     * | \ | (_)    | |
     * |  \| |_  ___| | __
     * | . ` | |/ __| |/ /
     * | |\  | | (__|   <
     * |_| \_|_|\___|_|\_\
     */

    /**
     * Change the player name for chat and even on his NameTag (aka Nick)
     *
     * @param Player $player
     * @param string $nick
     * @param bool $save
     * @return bool
     */
    public function setNick(Player $player, $nick, $save = true){
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerNickChangeEvent($this, $player, $nick));
        if($event->isCancelled()){
            return false;
        }
        $config = new Config($this->getDataFolder() . "Nicks.yml", Config::YAML);
        $nick = $event->getNewNick();
        $player->setNameTag($event->getNameTag());
        $player->setDisplayName($nick);
        $player->sendMessage(TextFormat::YELLOW . "Your nick is now $nick");
        if($save == true){
            $config->set($player->getName(), $nick);
            $config->save();
        }
        return true;
    }

    /**
     * Restore the original player name for chat and on his NameTag
     *
     * @param Player $player
     * @param bool $save
     * @return bool
     */
    public function removeNick(Player $player, $save = true){
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerNickChangeEvent($this, $player, $player->getName()));
        if($event->isCancelled()){
            return false;
        }
        $config = new Config($this->getDataFolder() . "Nicks.yml", Config::YAML);
        $nick = $event->getNewNick();
        $player->setNameTag($event->getNameTag());
        $player->setDisplayName($nick);
        $player->sendMessage(TextFormat::YELLOW . "Your nick is now $nick");
        if($save == true){
            $config->set($player->getName(), $nick);
            $config->save();
        }
        return true;
    }

    /**
     * Get players' saved Nicks
     *
     * @param Player $player
     * @return bool|mixed
     */
    public function getNick(Player $player){
        $config = new Config($this->getDataFolder() . "Nicks.yml", Config::YAML);
        if(!$config->exists($player->getName())){
            return $player->getName();
        }
        return $config->get($player->getName());
    }

    /**  _____                    _______          _
     *  |  __ \                  |__   __|        | |
     *  | |__) _____      _____ _ __| | ___   ___ | |
     *  |  ___/ _ \ \ /\ / / _ | '__| |/ _ \ / _ \| |
     *  | |  | (_) \ V  V |  __| |  | | (_) | (_) | |
     *  |_|   \___/ \_/\_/ \___|_|  |_|\___/ \___/|_|
     */

    /**
     * Tell is PowerTool is enabled for a player, doesn't matter on what item
     *
     * @param Player $player
     * @return bool
     */
    public function isPowerToolEnabled(Player $player){
        if($this->sessions[$player->getName()]["powertool"]["commands"] === false || $this->sessions[$player->getName()]["powertool"]["chat-macro"] === false){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Run all the commands and send all the chat messages assigned to an item
     *
     * @param Player $player
     * @param Item $item
     */
    public function executePowerTool(Player $player, Item $item){
        if($this->getPowerToolItemCommands($player, $item)){
            if(is_array($this->getPowerToolItemCommands($player, $item))){
                foreach($this->getPowerToolItemCommands($player, $item) as $command){
                    $this->getServer()->dispatchCommand($player, $command);
                }
            }else{
                $this->getServer()->dispatchCommand($player, $this->getPowerToolItemCommands($player, $item));
            }
        }
        if($this->getPowerToolItemChatMacro($player, $item)){
            $this->getServer()->broadcast("<" . $player->getDisplayName() . "> " . $this->getPowerToolItemChatMacro($player, $item), Server::BROADCAST_CHANNEL_USERS);
        }
    }

    /**
     * Sets a command for the item you have in hand
     * NOTE: If the hand is empty, it will be cancelled
     *
     * @param Player $player
     * @param Item $item
     * @param string $command
     */
    public function setPowerToolItemCommand(Player $player, Item $item, $command){
        if($item->getID() === 0){
            return;
        }
        $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()] = $command;
    }

    /**
     * Add multiple commands (or a single command to the list) to the item you have in hand
     * NOTE: If the hand is empty, it will be cancelled
     *
     * @param Player $player
     * @param Item $item
     * @param array $commands
     */
    public function setPowerToolItemCommands(Player $player, Item $item, array $commands){
        if($item->getID() === 0){
            return;
        }
        foreach($commands as $c){
            $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()][] = $c;
        }
    }

    /**
     * @param Player $player
     * @param Item $item
     * @param string $command
     */
    public function removePowerToolCommand(Player $player, Item $item, $command){
        if(!isset($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()])){
            return;
        }
        $list = $this->getPowerToolItemCommands($player, $item);
        if($key = array_search($command, $list) !== false){
            unset($list[$key]);
        }
    }

    /**
     * Return the command attached to the specified item if it's available
     *
     * @param Player $player
     * @param Item $item
     * @return bool|array
     */
    public function getPowerToolItemCommands(Player $player, Item $item){
        if(!isset($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()])){
            return false;
        }
        return $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()];
    }

    /**
     * Set a chat message to broadcast has the player
     *
     * @param Player $player
     * @param Item $item
     * @param string $chat_message
     */
    public function setPowerToolItemChatMacro(Player $player, Item $item, $chat_message){
        if($item->getID() === 0){
            return;
        }
        $chat_message = str_replace("%n", "\n", $chat_message);
        $this->sessions[$player->getName()]["powertool"]["chat-macro"][$item->getID()] = $chat_message;
    }

    /**
     * Get the message to broadcast has the player
     *
     * @param Player $player
     * @param Item $item
     * @return bool|string
     */
    public function getPowerToolItemChatMacro(Player $player, Item $item){
        if(!isset($this->sessions[$player->getName()]["powertool"]["chat-macro"][$item->getID()])){
            return false;
        }
        return $this->sessions[$player->getName()]["powertool"]["chat-macro"][$item->getID()];
    }

    /**
     * Remove the command only for the item in hand
     *
     * @param Player $player
     * @param Item $item
     */
    public function disablePowerToolItem(Player $player, Item $item){
        unset($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()]);
        unset($this->sessions[$player->getName()]["powertool"]["chat-macro"][$item->getID()]);
    }

    /**
     * Remove the commands for all the items of a player
     *
     * @param Player $player
     */
    public function disablePowerTool(Player $player){
        $this->sessions[$player->getName()]["powertool"]["commands"] = false;
        $this->sessions[$player->getName()]["powertool"]["chat-macro"] = false;
    }

    /**  _____        _____
     *  |  __ \      |  __ \
     *  | |__) __   _| |__) |
     *  |  ___/\ \ / |  ___/
     *  | |     \ V /| |
     *  |_|      \_/ |_|
     */

    /**
     * Set the PvP mode on or off
     *
     * @param Player $player
     * @param bool $state
     * @return bool
     */
    public function setPvP(Player $player, $state){
        if(!is_bool($state)){
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerPvPModeChangeEvent($this, $player, $state));
        if($ev->isCancelled()){
            return false;
        }
        $state = $ev->getPvPMode();
        $this->setSession($player, "pvp", $state);
        return true;
    }

    /**
     * Switch the PvP mode on/off automatically
     *
     * @param Player $player
     */
    public function switchPvP(Player $player){
        if(!$this->isPvPEnabled($player)){
            $this->setPvP($player, true);
        }else{
            $this->setPvP($player, false);
        }
    }

    /**
     * Tell if the PvP mode is enabled for the specified player, or not
     *
     * @param Player $player
     * @return bool
     */
    public function isPvPEnabled(Player $player){
        return $this->getSession($player, "pvp");
    }

    /** __          __
     *  \ \        / /
     *   \ \  /\  / __ _ _ __ _ __
     *    \ \/  \/ / _` | '__| '_ \
     *     \  /\  | (_| | |  | |_) |
     *      \/  \/ \__,_|_|  | .__/
     *                       | |
     *                       |_|
     */

    /**
     * Set's a new Warp or modify the position if already exists
     * it use Player to handle the position, but may change later
     *
     * @param Player $player
     * @param string $warp
     */
    public function setWarp(Player $player, $warp){
        $config = new Config($this->getDataFolder() . "Warps.yml", Config::YAML);
        $pos = array();
        $pos["x"] = $player->getX();
        $pos["y"] = $player->getY();
        $pos["z"] = $player->getZ();
        $pos["yaw"] = $player->yaw;
        $pos["pitch"] = $player->pitch;
        $pos["level"] = $player->getLevel()->getName();
        $config->set($warp, $pos);
    }

    /**
     * Remove a Warp if exists
     *
     * @param string $warp
     * @return bool
     */
    public function removeWarp($warp){
        $config = new Config($this->getDataFolder() . "Warps.yml", Config::YAML);
        if(!$this->warpExist($warp)){
            return false;
        }else{
            $config->remove($warp);
            return true;
        }
    }

    /**
     * Tell if a Warp exists
     *
     * @param string $warp
     * @return bool
     */
    public function warpExist($warp){
        $config = new Config($this->getDataFolder() . "Warps.yml", Config::YAML);
        if(!$config->exists($warp)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Teleport a player to a Warp
     *
     * @param Player $player
     * @param string $warp
     * @return bool
     */
    public function tpWarp(Player $player, $warp){
        $config = new Config($this->getDataFolder() . "Warps.yml", Config::YAML);
        if(!$config->exists($warp)){
            return false;
        }
        $home = $config->get($warp);
        if($player->getLevel()->getName() != $home["level"]){
            $player->setLevel($home["level"]);
        }
        $player->teleport(new Vector3($home["x"], $home["y"], $home["z"]), $home["yaw"], $home["pitch"]);
        return true;
    }

    /**
     * Return a list with all the available warps
     *
     * TODO
     */
    public function warpList(){
        //NOTE: Consider using wordwrap($string, $width, "\n", true)
    }

    /**  _    _       _ _           _ _           _   _____ _
     *  | |  | |     | (_)         (_| |         | | |_   _| |
     *  | |  | |_ __ | |_ _ __ ___  _| |_ ___  __| |   | | | |_ ___ _ __ ___  ___
     *  | |  | | '_ \| | | '_ ` _ \| | __/ _ \/ _` |   | | | __/ _ | '_ ` _ \/ __|
     *  | |__| | | | | | | | | | | | | ||  __| (_| |  _| |_| ||  __| | | | | \__ \
     *   \____/|_| |_|_|_|_| |_| |_|_|\__\___|\__,_| |_____|\__\___|_| |_| |_|___/
     */

    /**
     * Set the unlimited place of items on/off to a player
     *
     * @param Player $player
     * @param bool $mode
     * @return bool
     */
    public function setUnlimited(Player $player, $mode){
        if(!is_bool($mode)){
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerUnlimitedModeChangeEvent($this, $player, $mode));
        if($ev->isCancelled()){
            return false;
        }
        $mode = $ev->getUnlimitedMode();
        $this->setSession($player, "unlimited", $mode);
        return true;
    }

    /**
     * @param Player $player
     */
    public function switchUnlimited(Player $player){
        if(!$this->isUnlimitedEnabled($player)){
            $this->setUnlimited($player, true);
        }else{
            $this->setUnlimited($player, false);
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isUnlimitedEnabled(Player $player){
        return $this->getSession($player, "unlimited");
    }

    /** __      __         _     _
     *  \ \    / /        (_)   | |
     *   \ \  / __ _ _ __  _ ___| |__
     *    \ \/ / _` | '_ \| / __| '_ \
     *     \  | (_| | | | | \__ | | | |
     *      \/ \__,_|_| |_|_|___|_| |_|
     */

    /**
     * Set the Vanish mode on or off
     *
     * @param Player $player
     * @param bool $state
     * @return bool
     */
    public function setVanish(Player $player, $state){
        if(!is_bool($state)){
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerVanishEvent($this, $player, $state));
        if($ev->isCancelled()){
            return false;
        }
        $state = $ev->willVanish();
        $this->setSession($player, "vanish", $state);
        if($state === false){
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $p->showPlayer($player);
            }
        }else{
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $p->hidePlayer($player);
            }
        }
        return true;
    }

    /**
     * Switch the Vanish mode on/off automatically
     *
     * @param Player $player
     * @return bool
     */
    public function switchVanish(Player $player){
        if(!$this->isVanished($player)){
            $this->setVanish($player, true);
        }else{
            $this->setVanish($player, false);
        }
    }

    /**
     * Tell if a player is Vanished, or not
     *
     * @param Player $player
     * @return bool
     */
    public function isVanished(Player $player){
        return $this->getSession($player, "vanish");
    }

    /**
     * Allow to switch between levels Vanished!
     * You need to teleport the player to a different level in order to call this event
     *
     * @param Player $player
     * @param Level $origin
     * @param Level $target
     */
    public function switchLevelVanish(Player $player, Level $origin, Level $target){
        if($origin->getName() !== $target->getName() && $this->isVanished($player)){
            foreach($origin->getPlayers() as $p){
                $p->showPlayer($player);
            }
            foreach($target->getPlayers() as $p){
                $p->hidePlayer($p);
            }
        }
    }
}
