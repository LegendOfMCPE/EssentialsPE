<?php
namespace EssentialsPE;

use EssentialsPE\Commands\AFK;
use EssentialsPE\Commands\Back;
use EssentialsPE\Commands\BreakCommand;
use EssentialsPE\Commands\Broadcast;
use EssentialsPE\Commands\Burn;
use EssentialsPE\Commands\ClearInventory;
use EssentialsPE\Commands\Compass;
use EssentialsPE\Commands\Depth;
use EssentialsPE\Commands\EssentialsPE;
use EssentialsPE\Commands\Extinguish;
use EssentialsPE\Commands\GetPos;
use EssentialsPE\Commands\God;
use EssentialsPE\Commands\Heal;
use EssentialsPE\Commands\Home\DelHome;
use EssentialsPE\Commands\Home\Home;
use EssentialsPE\Commands\Home\SetHome;
use EssentialsPE\Commands\ItemCommand;
use EssentialsPE\Commands\ItemDB;
use EssentialsPE\Commands\Jump;
use EssentialsPE\Commands\KickAll;
use EssentialsPE\Commands\More;
use EssentialsPE\Commands\Mute;
use EssentialsPE\Commands\Near;
use EssentialsPE\Commands\Nick;
use EssentialsPE\Commands\Nuke;
use EssentialsPE\Commands\Override\Kill;
use EssentialsPE\Commands\PowerTool\PowerTool;
use EssentialsPE\Commands\PowerTool\PowerToolToggle;
use EssentialsPE\Commands\PvP;
use EssentialsPE\Commands\RealName;
use EssentialsPE\Commands\Repair;
use EssentialsPE\Commands\Seen;
use EssentialsPE\Commands\SetSpawn;
use EssentialsPE\Commands\Spawn;
use EssentialsPE\Commands\Sudo;
use EssentialsPE\Commands\Suicide;
use EssentialsPE\Commands\Teleport\TPA;
use EssentialsPE\Commands\Teleport\TPAccept;
use EssentialsPE\Commands\Teleport\TPAHere;
use EssentialsPE\Commands\Teleport\TPAll;
use EssentialsPE\Commands\Teleport\TPDeny;
use EssentialsPE\Commands\Teleport\TPHere;
use EssentialsPE\Commands\TempBan;
use EssentialsPE\Commands\Top;
use EssentialsPE\Commands\Unlimited;
use EssentialsPE\Commands\Vanish;
use EssentialsPE\Commands\Warp\DelWarp;
use EssentialsPE\Commands\Warp\Setwarp;
use EssentialsPE\Commands\Warp\Warp;
use EssentialsPE\Commands\World;
use EssentialsPE\Events\PlayerAFKModeChangeEvent;
use EssentialsPE\Events\PlayerGodModeChangeEvent;
use EssentialsPE\Events\PlayerMuteEvent;
use EssentialsPE\Events\PlayerNickChangeEvent;
use EssentialsPE\Events\PlayerPvPModeChangeEvent;
use EssentialsPE\Events\PlayerUnlimitedModeChangeEvent;
use EssentialsPE\Events\PlayerVanishEvent;
use EssentialsPE\Events\SessionCreateEvent;
use EssentialsPE\Tasks\AFKKickTask;
use EssentialsPE\Tasks\TPRequestTask;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase{
    /** @var Config */
    public  $homes;
    /** @var Config  */
    public $nicks;
    /** @var Config */
    public $warps;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->checkConfig();
        $this->saveConfigs();
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
            //Sessions
            $this->removeSession($p);
        }
    }

    /**
     * Function to easily disable commands
     *
     * @param array $commands
     */
    private function unregisterCommands(array $commands){
        $commandmap = $this->getServer()->getCommandMap();

        foreach($commands as $commandlabel){
            $command = $commandmap->getCommand($commandlabel);
            $command->setLabel($commandlabel . "_disabled");
            $command->unregister($commandmap);
        }
    }

    /**
     * Function to register all EssentialsPE's commands...
     * And to override some default ones
     */
    private function registerCommands(){
        //Unregister commands to override
        $this->unregisterCommands([
           //"gamemode", // TODO: ReWrite
            "kill"
        ]);
        $commands = [];
        $regex = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getFile() . "/src/" . __NAMESPACE__ . "/Commands/")), '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
        foreach ($regex as $file) {
            $class = str_replace("/", "\\", substr($file[0], strpos($file[0], __NAMESPACE__ . "/Commands/"), -4));
            $commands[] = new $class($this);
        }

        //Register the new commands
        $cmdmap = $this->getServer()->getCommandMap();
        $cmdmap->registerAll("essentialspe", $commands);
    }

    public function checkConfig(){
        $this->saveDefaultConfig();
        $cfg = $this->getConfig();

        $booleans = ["safe-afk", "enable-custom-colors"];
        foreach($booleans as $key){
            if(!$cfg->exists($key) || !is_bool($cfg->get($key))){
                switch($key){
                    // Properties to auto set true
                    case "safe-afk":
                        $cfg->set($key, true);
                        break;
                    // Properties to auto set false
                    case "enable-custom-colors":
                        $cfg->set($key, false);
                        break;
                }
            }
        }

        $numerics = ["auto-afk-kick", "oversized-stacks", "near-radius-limit", "near-default-radius"];
        foreach($numerics as $key){
            if(!is_numeric($cfg->get($key))){
                switch($key){
                    case "auto-afk-kick":
                        $cfg->set($key, 300);
                        break;
                    case "oversized-stacks":
                        $cfg->set($key, 64);
                        break;
                    case "near-radius-limit":
                        $cfg->set($key, 200);
                        break;
                    case "near-default-radius":
                        $cfg->set($key, 100);
                        break;
                }
            }
        }

        $cfg->save();
        $cfg->reload();
    }

    private function saveConfigs(){
        $this->homes = new Config($this->getDataFolder() . "Homes.yml", Config::YAML);
        $this->nicks = new Config($this->getDataFolder() . "Nicks.yml", Config::YAML);
        $this->warps = new Config($this->getDataFolder() . "Warps.yml", Config::YAML);
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
     * @param string $player
     * @return bool|Player
     */
    public function getPlayer($player){
        $player = strtolower($player);
        foreach($this->getServer()->getOnlinePlayers() as $p){
            if(strtolower($p->getDisplayName()) === $player || strtolower($p->getName()) === $player){
                return $p;
                break;
            }
        }
        return false;
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
        foreach($search as $s){
            $f = str_replace("&", "§", $s);
            $message = str_replace($s, $f, $message);
            $message = str_replace("\\" . $f, $s, $message);
        }
        if(strpos($message, "§") !== false && ($player instanceof Player) && !$player->hasPermission("essentials.colorchat")){
            $player->sendMessage(TextFormat::RED . "You can't chat using colors!");
            return false;
        }
        return $message;
    }

    /**
     * Let you know if the item is a Tool or Armor
     * (Items that can get "real damage"
     *
     * @param Item $item
     * @return bool
     */
    public  function isReparable(Item $item){
        $IDs = [
                               /** Wood */            /** Stone */             /** Iron */            /** Gold */              /** Diamond */
            /** Swords */   Item::WOODEN_SWORD,     Item::STONE_SWORD,      Item::IRON_SWORD,       Item::GOLD_SWORD,       Item::DIAMOND_SWORD,
            /** Shovels */  Item::WOODEN_SHOVEL,    Item::STONE_SHOVEL,     Item::IRON_SHOVEL,      Item::GOLD_SHOVEL,      Item::DIAMOND_SHOVEL,
            /** Pickaxes */ Item::WOODEN_PICKAXE,   Item::STONE_PICKAXE,    Item::IRON_PICKAXE,     Item::GOLD_PICKAXE,     Item::DIAMOND_PICKAXE,
            /** Axes */     Item::WOODEN_AXE,       Item::STONE_AXE,        Item::IRON_AXE,         Item::GOLD_AXE,         Item::DIAMOND_AXE,
            /** Hoes */     Item::WOODEN_HOE,       Item::STONE_HOE,        Item::IRON_HOE,         Item::GOLD_HOE,         Item::DIAMOND_HOE,


                                   /** Leather */          /** Chain */                /** Iron */                 /** Gold */                 /** Diamond */
            /** Boots */        Item::LEATHER_BOOTS,    Item::CHAIN_BOOTS,          Item::IRON_BOOTS,           Item::GOLD_BOOTS,           Item::DIAMOND_BOOTS,
            /** Leggings */     Item::LEATHER_PANTS,    Item::CHAIN_LEGGINGS,       Item::IRON_LEGGINGS,        Item::GOLD_LEGGINGS,        Item::DIAMOND_LEGGINGS,
            /** Chestplates */  Item::LEATHER_TUNIC,    Item::CHAIN_CHESTPLATE,     Item::IRON_CHESTPLATE,      Item::GOLD_CHESTPLATE,      Item::DIAMOND_CHESTPLATE,
            /** Helmets */      Item::LEATHER_CAP,      Item::CHAIN_HELMET,         Item::IRON_HELMET,          Item::GOLD_HELMET,          Item::DIAMOND_HELMET,


            /** Other */    Item::BOW, Item::FLINT_AND_STEEL, Item::SHEARS
        ];
        return !isset($IDs[$item->getId()]);
    }

    /**
     * Let you see who is near a specific player
     *
     * @param Player $player
     * @param int $radius
     * @return bool|Player[]
     */
    public function getNearPlayers(Player $player, $radius = null){
        if($radius === null){
            $radius = $this->getConfig()->get("near-default-radius");
        }
        if(!is_numeric($radius)){
            return false;
        }
        $radius = new AxisAlignedBB($player->getFloorX() - $radius, $player->getFloorY() - $radius, $player->getFloorZ() - $radius, $player->getFloorX() + $radius, $player->getFloorY() + $radius, $player->getFloorZ() + $radius);
        $entities = $player->getLevel()->getNearbyEntities($radius, $player);
        $players = [];
        foreach($entities as $e){
            if($e instanceof Player){
                $player[] = $e;
            }
        }
        return $players;
    }

    /**
     * Spawn a carpet of bomb!
     *
     * @param Player $player
     */
    public function nuke(Player $player){
        for($x = -10; $x <= 10; $x += 5){
            for($z = -10; $z <= 10; $z += 5){
                $pos = new Vector3($player->getFloorX() + $x, $player->getFloorY(), $player->getFloorZ() + $z);
                $level = $player->getLevel();
                $mot = (new Random())->nextSignedFloat() * M_PI * 2;
                $tnt = Entity::createEntity("PrimedTNT", $level->getChunk($pos->x >> 4, $pos->z >> 4), new Compound("", [
                    "Pos" => new Enum("Pos", [
                        new Double("", $pos->x + 0.5),
                        new Double("", $pos->y),
                        new Double("", $pos->z + 0.5)
                    ]),
                    "Motion" => new Enum("Motion", [
                        new Double("", -sin($mot) * 0.02),
                        new Double("", 0.2),
                        new Double("", -cos($mot) * 0.02)
                    ]),
                    "Rotation" => new Enum("Rotation", [
                        new Float("", 0),
                        new Float("", 0)
                    ]),
                    "Fuse" => new Byte("Fuse", 80),
                ]));
                $tnt->namedtag->setName("EssNuke");
                $tnt->spawnToAll();
            }
        }
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

    /**
     * Tell if a session exists for a specific player
     *
     * @param Player $player
     * @return bool
     */
    public function sessionExists(Player $player){
        return isset($this->sessions[$player->getId()]);
    }

    /**
     * Creates a new Sessions for the specified player
     *
     * @param Player $player
     */
    public function createSession(Player $player){
        $this->getServer()->getPluginManager()->callEvent($ev = new SessionCreateEvent($this, $player, [
            "isAFK" => false,
            "kickAFK" => null,
            "autoAFK" => null,
            "lastPosition" => null,
            "lastRotation" => null,
            "isGod" => false,
            "ptCommands" => false,
            "ptChatMacros" => false,
            "isPvPEnabled" => true,
            "requestTo" => false,
            "requestToAction" => false,
            "requestToTask" => null,
            "latestRequestFrom" => null,
            "requestsFrom" => [],
            "isUnlimitedEnabled" => false,
            "isVanished" => false
        ]));
        $this->sessions[$player->getId()] = new BaseSession($ev->getValues());

        //Enable Custom Colored Chat
        if($this->getConfig()->get("enable-custom-colors") === true){
            $player->setRemoveFormat(false);
        }
    }

    /**
     * Remove player's session (if active and available)
     *
     * @param Player $player
     */
    public function removeSession(Player $player){
        unset($this->sessions[$player->getId()]);

        //Disable Custom Colored Chat
        if($this->getConfig()->get("enable-custom-colors") === true){
            $player->setRemoveFormat(true);
        }
    }

    /**
     * Return the value of a session key
     *
     * @param Player $player
     * @return bool|BaseSession
     */
    public function getSession(Player $player){
        if(!$this->sessionExists($player)){
            return false;
        }
        return $this->sessions[$player->getId()];
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
     * Also
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
        $this->getSession($player)->isAFK = $state;
        if($state === false && ($id = $this->getAFKAutoKickTaskID($player)) !== false){
            $this->getServer()->getScheduler()->cancelTask($id);
            $this->removeAFKAutoKickTaskID($player);
        }elseif($state === true and (($time = $this->getAFKAutoKickTime()) !== false and $time  > 0) and !$player->hasPermission("essentials.afk.kickexempt")){
            $task = $this->getServer()->getScheduler()->scheduleDelayedTask(new AFKKickTask($this, $player), ($time * 20));
            $this->setAFKAutoKickTaskID($player, $task->getTaskId());
        }
        return true;
    }

    /**
     * Automatically switch the AFK mode on/off
     *
     * @param Player $player
     */
    public function switchAFKMode(Player $player){
        $this->setAFKMode($player, ($this->isAFK($player) ? false : true));
    }

    /**
     * Tell if the player is AFK or not
     *
     * @param Player $player
     * @return bool
     */
    public function isAFK(Player $player){
        return $this->getSession($player)->isAFK;
    }

    /**
     * Get the time until a player get kicked for AFK
     *
     * @return bool|int
     */
    public function getAFKAutoKickTime(){
        return $this->getConfig()->get("auto-afk-kick");
    }

    /**
     * Set the Auto-Kick TaskID of the player
     *
     * @param Player $player
     * @param int $taskID
     */
    private function setAFKAutoKickTaskID(Player $player, $taskID){
        $this->getSession($player)->kickAFK = $taskID;
    }

    /**
     * Removes the Auto-Kick TaskID of the player
     *
     * @param Player $player
     */
    private function removeAFKAutoKickTaskID(Player $player){
        $this->getSession($player)->kickAFK = false;
    }

    /**
     * Return the Auto-kick TaskID of a player for being AFK
     * Return "false" if the player isn't AFK or isn't on a Kick Queue
     *
     * @param Player $player
     * @return mixed
     */
    private function getAFKAutoKickTaskID(Player $player){
        if(!$this->isAFK($player)){
            return false;
        }
        return $this->getSession($player)->kickAFK;
    }

    /**  ____             _
     *  |  _ \           | |
     *  | |_) | __ _  ___| | __
     *  |  _ < / _` |/ __| |/ /
     *  | |_) | (_| | (__|   <
     *  |____/ \__,_|\___|_|\_\
     */

    /**
     * Return the last known spot of a player before teleporting
     *
     * @param Player $player
     * @return bool|Position
     */
    public function getLastPlayerPosition(Player $player){
        if(!$this->getSession($player)->lastPosition instanceof Position){
            return false;
        }
        return $this->getSession($player)->lastPosition;
    }

    /**
     * Get the last known rotation of a player before teleporting
     *
     * @param Player $player
     * @return bool|array
     */
    public function getLastPlayerRotation(Player $player){
        if(count($this->getSession($player)->lastRotation) !== 2){
            return false;
        }
        return $this->getSession($player)->lastRotation;
    }

    /**
     * Updates the last position of a player.
     *
     * @param Player $player
     * @param Position $pos
     * @param int $yaw
     * @param int $pitch
     */
    public function setPlayerLastPosition(Player $player, Position $pos, $yaw, $pitch){
        $this->getSession($player)->lastPosition = $pos;
        $this->getSession($player)->lastRotation = [$yaw, $pitch];
    }

    public function removePlayerLastPosition(Player $player){
        $this->getSession($player)->lastPosition = null;
        $this->getSession($player)->lastRotation = null;
    }

    /**
     * Teleport the target player to its last known spot and set the corresponding rotation
     *
     * @param Player $player
     * @return bool
     */
    public function returnPlayerToLastKnownPosition(Player $player){
        $pos = $this->getLastPlayerPosition($player);
        $rotation = $this->getLastPlayerRotation($player);
        if(!$pos && !$rotation){
            return false;
        }
        $player->teleport($pos, $rotation[0], $rotation[1]);
        return true;
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
        $this->getSession($player)->isGod = $ev->getGodMode();
        return true;
    }

    /**
     * Switch God Mode on/off automatically
     *
     * @param Player $player
     */
    public function switchGodMode(Player $player){
        $this->setGodMode($player, ($this->isGod($player) ? false : true));
    }

    /**
     * Tell if a player is in God Mode
     *
     * @param Player $player
     * @return bool
     */
    public function isGod(Player $player){
        return $this->getSession($player)->isGod;
    }

    /**  _    _
     *  | |  | |
     *  | |__| | ___  _ __ ___   ___ ___
     *  |  __  |/ _ \| '_ ` _ \ / _ / __|
     *  | |  | | (_) | | | | | |  __\__ \
     *  |_|  |_|\___/|_| |_| |_|\___|___/
     */

    /**
     * Tell is a player have a specific home by its name
     *
     * @param Player $player
     * @param string $home
     * @return bool
     */
    public function homeExists(Player $player, $home){
        $list = $this->homes->get($player->getName());
        if(!$list){
            return false;
        }
        $list = explode(";", $list);
        foreach($list as $h){
            $h = explode(",", $h);
            if($h[0] === strtolower($home)){
                return true;
                break;
            }
        }
        return false;
    }

    /**
     * Return the home information (Position and Rotation)
     *
     * @param Player $player
     * @param string $home
     * @return bool|array
     */
    public function getHome(Player $player, $home){
        if(!$this->homeExists($player, $home)){
            return false;
        }
        $list = explode(";", $this->homes->get($player->getName()));
        foreach($list as $h){
            $h = explode(",", $h);
            if($h[0] === strtolower($home)){
                unset($h[0]);
                $home = $h;
                break;
            }
        }
        if(!$this->getServer()->isLevelLoaded($home[4])){
            if(!$this->getServer()->isLevelGenerated($home[4])){
                return false;
            }
            $this->getServer()->loadLevel($home[4]);
        }
        return [new Position($home[1], $home[2], $home[3], $this->getServer()->getLevelByName($home[4])), $home[5], $home[6]];
    }

    /**
     * Create or update a home
     *
     * @param Player $player
     * @param string $home
     * @param Position $pos
     * @param int $yaw
     * @param int $pitch
     */
    public function setHome(Player $player, $home, Position $pos, $yaw = 0, $pitch = 0){
        if(trim($home) === ""){
            return;
        }
        $homestring = strtolower($home) . "," . $pos->getX() . "," . $pos->getY() . "," . $pos->getZ() . ","  . $pos->getLevel()->getName() . "," . $yaw . "," . $pitch;
        if($this->homeExists($player, $home)){
            $this->removeHome($player, $home);
        }
        if(($homes = $this->homes->get($player->getName())) !== false && $homes !== ""){
            $homestring = ";" . $homestring;
        }
        $this->homes->set($player->getName(), $homestring);
        $this->homes->save();
    }

    /**
     * Removes a home
     *
     * @param Player $player
     * @param string $home
     */
    public function removeHome(Player $player, $home){
        if($this->homeExists($player, $home)){
            $homes = explode(";", $this->homes->get($player->getName()));
            foreach($homes as $k => $h){
                $name = explode(",", $h);
                if($name[0] === strtolower($home)){
                    array_splice($homes, $k, 1);
                    break;
                }
            }
            $this->homes->set($player->getName(), implode(";", $homes));
            if(($homes = $this->homes->get($player->getName())) === "" || $homes === null || $homes === " "){
                $this->homes->remove($player->getName());
            }
            $this->homes->save();
        }
    }

    /**
     * Return a list of all the available homes of a certain player
     *
     * @param Player $player
     * @param bool $inArray
     * @return array|bool|string
     */
    public function homesList(Player $player, $inArray = false){
        $list = $this->homes->get($player->getName());
        if(!$list){
            return false;
        }
        $homes = explode(";", $list);
        $list = [];
        foreach($homes as $home){
            $home = explode(",", $home);
            $list[] = $home[0];
        }
        if($list === []){
            return false;
        }
        if(!$inArray){
            $string = wordwrap(implode(", ", $list), 30, "\n", true);
            return $string;
        }
        return $list;
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
        if(!isset($this->mutes[$player->getId()])){
            $this->mutes[$player->getId()] = false;
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
        $this->mutes[$player->getId()] = $ev->willMute();
        return true;
    }

    /**
     * Switch the Mute mode on/off automatically
     *
     * @param Player $player
     */
    public function switchMute(Player $player){
        $this->setMute($player, ($this->isMuted($player) ? false : true));
    }

    /**
     * Tell if the is Muted or not
     *
     * @param Player $player
     * @return bool
     */
    public function isMuted(Player $player){
        return $this->mutes[$player->getId()];
    }

    /**  _   _ _      _
     *  | \ | (_)    | |
     *  |  \| |_  ___| | _____
     *  | . ` | |/ __| |/ / __|
     *  | |\  | | (__|   <\__ \
     *  |_| \_|_|\___|_|\_|___/
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
        $config = $this->nicks;
        $nick = $event->getNewNick();
        $player->setNameTag($event->getNameTag());
        $player->setDisplayName($nick);
        if($save == true){
            if($nick === $player->getName() || $nick === "off"){
                $config->remove($player->getName());
            }else{
                $config->set($player->getName(), $nick);
            }
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
        $config = $this->nicks;
        $nick = $event->getNewNick();
        $player->setNameTag($event->getNameTag());
        $player->setDisplayName($nick);
        if($save == true){
            if($nick === $player->getName() || $nick === "off"){
                $config->remove($player->getName());
            }else{
                $config->set($player->getName(), $nick);
            }
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
        $config = $this->nicks;
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
        if($this->getSession($player)->ptCommands === false || $this->getSession($player)->ptChatMacro === false){
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
     * @return bool
     */
    public function executePowerTool(Player $player, Item $item){
        $command = false;
        if($this->getPowerToolItemCommand($player, $item) !== false){
            $command = $this->getPowerToolItemCommand($player, $item);
        }elseif($this->getPowerToolItemCommands($player, $item) !== false){
            $command = $this->getPowerToolItemCommands($player, $item);
        }
        if($command !== false){
            if(!is_array($command)){
                $this->getServer()->dispatchCommand($player, $command);
            }else{
                foreach($command as $c){
                    $this->getServer()->dispatchCommand($player, $c);
                }
            }
        }
        if($chat = $this->getPowerToolItemChatMacro($player, $item) !== false){
            $this->getServer()->broadcast("<" . $player->getDisplayName() . "> " . TextFormat::RESET . $this->getPowerToolItemChatMacro($player, $item), Server::BROADCAST_CHANNEL_USERS);
        }
        if($command === false && $chat === false){
            return false;
        }
        return true;
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
        if($item->getID() !== 0){
            if(!is_array($this->getSession($player)->ptCommands[$item->getID()])){
                $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()] = $command;
            }else{
                $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()][] = $command;
            }
        }
    }

    /**
     * Return the command attached to the specified item if it's available
     * NOTE: Only return the command if there're no more commands, for that use "getPowerToolItemCommands" (note the "s" at the final :P)
     *
     * @param Player $player
     * @param Item $item
     * @return bool|string
     */
    public function getPowerToolItemCommand(Player $player, Item $item){
        if($item->getId() === 0 || (!isset($this->getSession($player)->ptCommands[$item->getID()]) || is_array($this->getSession($player)->ptCommands[$item->getID()]))){
            return false;
        }
        return $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()];
    }

    /**
     * Let you assign multiple commands to an item
     *
     * @param Player $player
     * @param Item $item
     * @param array $commands
     * @return bool
     */
    public function setPowerToolItemCommands(Player $player, Item $item, array $commands){
        if($item->getID() === 0){
            return false;
        }
        $this->getSession($player)->ptCommands = $commands;
        return true;
    }

    /**
     * Return a the list of commands assigned to an item
     * (if they're more than 1)
     *
     * @param Player $player
     * @param Item $item
     * @return bool|array
     */
    public function getPowerToolItemCommands(Player $player, Item $item){
        if(!isset($this->getSession($player)->ptCommands[$item->getID()]) || !is_array($this->getSession($player)->ptCommands[$item->getID()])){
            return false;
        }
        return $this->getSession($player)->ptCommands[$item->getID()];
    }

    /**
     *
     * Let you remove 1 command of the item command list
     * [if there're more than 1)
     *
     * @param Player $player
     * @param Item $item
     * @param string $command
     */
    public function removePowerToolItemCommand(Player $player, Item $item, $command){
        if(is_array($commands = $this->getPowerToolItemCommands($player, $item))){
            foreach($commands as $c){
                if(stripos(strtolower($c), strtolower($command)) !== false){
                    unset($c);
                }
            }
        }
    }

    /**
     * Set a chat message to broadcast has the player
     *
     * @param Player $player
     * @param Item $item
     * @param string $chat_message
     * @return bool
     */
    public function setPowerToolItemChatMacro(Player $player, Item $item, $chat_message){
        if($item->getID() === 0){
            return false;
        }
        $chat_message = str_replace("\\n", "\n", $chat_message);
        $this->getSession($player)->ptChatMacro[$item->getID()] = $chat_message;
        return true;
    }

    /**
     * Get the message to broadcast has the player
     *
     * @param Player $player
     * @param Item $item
     * @return bool|string
     */
    public function getPowerToolItemChatMacro(Player $player, Item $item){
        if(!isset($this->getSession($player)->ptChatMacro[$item->getID()])){
            return false;
        }
        return $this->getSession($player)->ptChatMacro[$item->getID()];
    }

    /**
     * Remove the command only for the item in hand
     *
     * @param Player $player
     * @param Item $item
     */
    public function disablePowerToolItem(Player $player, Item $item){
        unset($this->getSession($player)->ptCommands[$item->getID()]);
        unset($this->getSession($player)->ptChatMacro[$item->getID()]);
    }

    /**
     * Remove the commands for all the items of a player
     *
     * @param Player $player
     */
    public function disablePowerTool(Player $player){
        $this->getSession($player)->ptCommands = false;
        $this->getSession($player)->ptChatMacro = false;
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
        $this->getSession($player)->isPvPEnabled = $ev->getPvPMode();
        return true;
    }

    /**
     * Switch the PvP mode on/off automatically
     *
     * @param Player $player
     */
    public function switchPvP(Player $player){
        $this->setPvP($player, ($this->isPvPEnabled($player) ? false : true));
    }

    /**
     * Tell if the PvP mode is enabled for the specified player, or not
     *
     * @param Player $player
     * @return bool
     */
    public function isPvPEnabled(Player $player){
        return $this->getSession($player)->isPvPEnabled;
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

    /**
     * Tell if a player has a pending request
     * Return false if not
     * Return array with all the names of the requesters and the actions to perform of each:
     *      "tpto" means that the requester wants to tp to the target position
     *      "tphere" means that the requester wants to tp the target to its position
     *
     * @param Player $player
     * @return bool|array
     */
    public function hasARequest(Player $player){
        if($this->getSession($player)->latestRequestFrom === null){
            return false;
        }
        return $this->getSession($player)->requestsFrom;
    }

    /**
     * Return the name of the latest teleport requester for a specific player
     *
     * @param Player $player
     * @return bool|string
     */
    public function getLatestRequest(Player $player){
        if($this->getSession($player)->latestRequestFrom === null){
            return false;
        }
        return $this->getSession($player)->latestRequestFrom;
    }

    /**
     * Tell if a player ($target) as a request from a specific player ($requester)
     * Return false if not
     * Return the type of request made:
     *      "tpto" means that the requester wants to tp to the target position
     *      "tphere" means that the requester wants to tp the target to its position
     *
     * @param Player $target
     * @param Player $requester
     * @return bool|array
     */
    public function hasARequestFrom(Player $target, Player $requester){
        if(!isset($this->getSession($target)->requestsFrom[$requester->getName()])){
            return false;
        }
        return $this->getSession($target)->requestsFrom[$requester->getName()];
    }

    /**
     * Tell if a player made a request to another player
     * Return false if not
     * Return array with the name of the target and the action to perform:
     *      "tpto" means that the requester wants to tp to the target position
     *      "tphere" means that the requester wants to tp the target to its position
     *
     * @param Player $player
     * @return bool|array
     */
    public function madeARequest(Player $player){
        if($this->getSession($player)->requestTo === false){
            return false;
        }
        return [$this->getSession($player)->requestTo, $this->getSession($player)->requestToAction];
    }

    /**
     * Schedule a Request to move $requester to $target's position
     *
     * @param Player $requester
     * @param Player $target
     */
    public function requestTPTo(Player $requester, Player $target){
        $this->getSession($requester)->requestTo = $target->getName();
        $this->getSession($requester)->requestToAction = "tpto";

        $this->getSession($target)->latestRequestFrom = $requester->getName();
        $this->getSession($target)->requestsFrom[$requester->getName()] = "tpto";

        $this->scheduleTPRequestTask($requester);
    }

    /**
     * Schedule a Request to mode $target to $requester's position
     *
     * @param Player $requester
     * @param Player $target
     */
    public function requestTPHere(Player $requester, Player $target){

        $this->getSession($requester)->requestTo = $target->getName();
        $this->getSession($requester)->requestToAction = "tphere";

        $this->getSession($target)->latestRequestFrom = $requester->getName();
        $this->getSession($target)->requestsFrom[$requester->getName()] = "tphere";

        $this->scheduleTPRequestTask($requester);
    }

    /**
     * Cancel the Request made by a player
     *
     * @param Player $requester
     * @param Player $target
     * @return bool
     */
    public function removeTPRequest(Player $requester, Player $target = null){
        if($this->getSession($requester)->requestTo === false && $target === null){
            return false;
        }

        if($target !== null && $this->getSession($requester)->requestTo === $target->getName()){
            $this->getSession($requester)->requestTo = false;
            $this->getSession($requester)->requestToAction = false;
            unset($this->getSession($target)->requestsFrom[$requester->getName()]);
        }elseif($target === null){
            $this->getSession($requester)->requestTo = false;
            $this->getSession($requester)->requestToAction = false;
            unset($this->getSession($target)->requestsFrom[$requester->getName()]);
            if($this->getSession($target)->requestsFrom["latest"] === $requester->getName()){
                $this->getSession($target)->requestsFrom["latest"] = false;
            }
        }

        $this->cancelTPRequestTask($requester);
        return true;
    }

    /**
     * Schedule the Request auto-remover task (Internal use ONLY!)
     *
     * @param Player $player
     */
    private function scheduleTPRequestTask(Player $player){
        $task = $this->getServer()->getScheduler()->scheduleDelayedTask(new TPRequestTask($this, $player), 20 * 60 * 5);
        $this->setTPRequestTaskID($player, $task->getTaskId());
    }

    /**
     * Return the Task ID (Internal use ONLY!)
     *
     * @param Player $player
     * @return bool|int
     */
    private function getTPRequestTaskID(Player $player){
        if(!$this->madeARequest($player)){
            return false;
        }
        return $this->getSession($player)->requestToTask;
    }

    /**
     * Modify the Task ID (Internal use ONLY!)
     *
     * @param Player $player
     * @param int $id
     */
    private function setTPRequestTaskID(Player $player, $id){
        $this->getSession($player)->requestToTask = $id;
    }

    /**
     * Cancel the Task (Internal use ONLY!)
     *
     * @param Player $player
     */
    private function cancelTPRequestTask(Player $player){
        $this->getServer()->getScheduler()->cancelTask($this->getTPRequestTaskID($player));
        $this->getSession($player)->requestToTask = false;
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
        $this->getSession($player)->isUnlimitedEnabled = $ev->getUnlimitedMode();
        return true;
    }

    /**
     * @param Player $player
     */
    public function switchUnlimited(Player $player){
        $this->setUnlimited($player, ($this->isUnlimitedEnabled($player) ? false : true));
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isUnlimitedEnabled(Player $player){
        return $this->getSession($player)->isUnlimitedEnabled;
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
        $this->getSession($player)->isVanished = $state;
        if($state === false){
            foreach($player->getLevel()->getPlayers() as $p){
                $p->showPlayer($player);
            }
        }else{
            foreach($player->getLevel()->getPlayers() as $p){
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
        $this->setVanish($player, ($this->isVanished($player) ? false : true));
    }

    /**
     * Tell if a player is Vanished, or not
     *
     * @param Player $player
     * @return bool
     */
    public function isVanished(Player $player){
        return $this->getSession($player)->isVanished;
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
                $player->showPlayer($p);
            }
            foreach($target->getPlayers() as $p){
                $p->hidePlayer($player);
                if($this->isVanished($p)){
                    $player->hidePlayer($p);
                }
            }
        }
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
     * Tell if a warp exists
     *
     * @param string $warp
     * @return bool
     */
    public function warpExists($warp){
        return ($this->warps->exists(strtolower($warp)) ? true : false);
    }

    /**
     * Get an array with all the warp information
     * If the function returns "false", it means that the warp doesn't exists
     *
     * @param string $warp
     * @return bool|array
     */
    public function getWarp($warp){
        if(!$this->warpExists(strtolower($warp))){
            return false;
        }
        $warp = explode(",", $this->warps->get(strtolower($warp)));
        return [new Position($warp[0], $warp[1], $warp[2], $this->getServer()->getLevelByName($warp[3])), $warp[4], $warp[5]];
    }

    /**
     * Create a warp or override its position
     *
     * @param string $warp
     * @param Position $pos
     * @param int $yaw
     * @param int $pitch
     */
    public function setWarp($warp, Position $pos, $yaw = 0, $pitch = 0){
        if($warp === null || $warp === "" || $warp === " "){
            return;
        }
        $value = $pos->getX() . "," . $pos->getY() . "," . $pos->getZ() . ","  . $pos->getLevel()->getName() . "," . $yaw . "," . $pitch;
        $this->warps->set(strtolower($warp), $value);
        $this->warps->save();
    }

    /**
     * Removes a warp!
     * If the function return "false", it means that the warp doesn't exists
     *
     * @param string $warp
     * @return bool
     */
    public function removeWarp($warp){
        if(!$this->warpExists($warp)){
            return false;
        }
        $this->warps->remove(strtolower($warp));
        $this->warps->save();
        return true;
    }

    /**
     * Return a list of all the available warps
     *
     * @param bool $inArray
     * @return array|bool|string
     */
    public function warpList($inArray = false){
        $list = $this->warps->getAll(true);
        if(!$list){
            return false;
        }
        if($list === []){
            return false;
        }
        if(!$inArray){
            $string = wordwrap(implode(", ", $list), 30, "\n", true);
            return $string;
        }
        return $list;
    }
}
