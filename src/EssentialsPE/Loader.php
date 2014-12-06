<?php
namespace EssentialsPE;

use EssentialsPE\Commands\AFK;
use EssentialsPE\Commands\Back;
use EssentialsPE\Commands\Broadcast;
use EssentialsPE\Commands\Burn;
use EssentialsPE\Commands\ClearInventory;
use EssentialsPE\Commands\Compass;
use EssentialsPE\Commands\Depth;
use EssentialsPE\Commands\Essentials;
use EssentialsPE\Commands\Extinguish;
use EssentialsPE\Commands\GetPos;
use EssentialsPE\Commands\God;
use EssentialsPE\Commands\Heal;
use EssentialsPE\Commands\Home\DelHome;
use EssentialsPE\Commands\Home\Home;
use EssentialsPE\Commands\Home\SetHome;
use EssentialsPE\Commands\ItemCommand;
use EssentialsPE\Commands\ItemDB;
use EssentialsPE\Commands\KickAll;
use EssentialsPE\Commands\More;
use EssentialsPE\Commands\Mute;
use EssentialsPE\Commands\Near;
use EssentialsPE\Commands\Nick;
use EssentialsPE\Commands\Nuke;
use EssentialsPE\Commands\Override\Gamemode;
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
use EssentialsPE\Commands\Teleport\TPAll;
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
           "gamemode",
            "kill"
        ]);

        //Register the new commands
        $cmdmap = $this->getServer()->getCommandMap();
        $cmdmap->registerAll("essentialspe", [
            new AFK($this),
            new Back($this),
            //new BigTreeCommand($this), //TODO
            //new BreakCommand($this), //TODO
            new Broadcast($this),
            new Burn($this),
            new ClearInventory($this),
            new Compass($this),
            new Depth($this),
            new Essentials($this),
            new Extinguish($this),
            new Gamemode($this),
            new GetPos($this),
            new God($this),
            new Heal($this),
            new ItemCommand($this),
            new ItemDB($this),
            //new Jump($this), //TODO
            new KickAll($this),
            new Kill($this),
            new More($this),
            new Mute($this),
            new Near($this),
            new Nick($this),
            new Nuke($this),
            new PvP($this),
            new RealName($this),
            new Repair($this),
            new Seen($this),
            new SetSpawn($this),
            new Spawn($this),
            new Sudo($this),
            new Suicide($this),
            new TempBan($this),
            new Top($this),
            //new TreeCommand($this), //TODO
            new Unlimited($this),
            new Vanish($this),
            new World($this),

            //Home
            new DelHome($this),
            new Home($this),
            new SetHome($this),

            //PowerTool
            new PowerTool($this),
            new PowerToolToggle($this),

            //Teleport
            new TPAll($this),
            new TPHere($this),

            //Warp
            new DelWarp($this),
            new Setwarp($this),
            new Warp($this),
        ]);
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
        $r = false;
        foreach($IDs as $id){
            if($item->getID() === $id){
                $r = true;
            }
        }
        return $r;
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
    /** @var array  */
    private $default = [
        "afk" => [
            "mode" => false,
            "kick-taskID" => false,
            "auto-taskID" => false
        ],
        "back" => [
            "position" => false,
            "rotation" => false
        ],
        "god" => false,
        "powertool" => [
            "commands" => false,
            "chat-macro" => false
        ],
        "pvp" => false,
        "tprequests" => [
            "request_to" => [
                "player" => false,
                "action" => false,
            ],
            "request_from" => [
                "player" => false,
                "action" => false
            ]
        ],
        "unlimited" => false,
        "vanish" => false
    ];

    /**
     * Creates a new Sessions for the specified player
     *
     * @param Player $player
     */
    public function createSession(Player $player){
        $this->getServer()->getPluginManager()->callEvent($ev = new SessionCreateEvent($this, $player, $this->default));
        $this->sessions[$player->getName()] = $ev->getValues();

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
        unset($this->sessions[$player->getName()]);

        //Disable Custom Colored Chat
        if($this->getConfig()->get("enable-custom-colors") === true){
            $player->setRemoveFormat(true);
        }
    }

    /**
     * Modify the value of a session key (See "Mute" for example)
     *
     * @param Player $player
     * @param string $key
     * @param $value
     * @return bool
     */
    public function setSession(Player $player, $key, $value){
        if(!isset($this->sessions[$player->getName()])){
            return false;
        }
        $this->sessions[$player->getName()][$key] = $value;
        return true;
    }

    /**
     * Return the value of a session key
     *
     * @param Player $player
     * @param string $key
     * @return bool
     */
    public function getSession(Player $player, $key){
        if(!isset($this->sessions[$player->getName()]) || !isset($this->sessions[$player->getName()][$key])){
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
        $this->sessions[$player->getName()]["afk"]["mode"] = $state;
        if($state === false && ($id = $this->getAFKAutoKickTaskID($player)) !== false){
            $this->getServer()->getScheduler()->cancelTask($id);
            $this->sessions[$player->getName()]["afk"]["kick-taskID"] = false;
        }elseif($state === true && ($time = $this->getAFKAutoKickTime()) >= 0 && !$player->hasPermission("essentials.afk.kickexempt")){
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
        return $this->sessions[$player->getName()]["afk"]["mode"];
    }

    public function getAFKAutoKickTime(){
        return $this->getConfig()->get("auto-afk-kick");
    }

    /**
     * Set the TaskID of the player afk-kick-timer
     *
     * @param Player $player
     * @param int $taskID
     */
    public function setAFKAutoKickTaskID(Player $player, $taskID){
        $this->sessions[$player->getName()]["afk"]["kick-taskID"] = $taskID;
    }

    /**
     * Return the Auto-kick TaskID of a player for being AFK
     * Return "false" if the player isn't AFK or isn't on a Kick Queue
     *
     * @param Player $player
     * @return mixed
     */
    public function getAFKAutoKickTaskID(Player $player){
        if(!$this->isAFK($player)){
            return false;
        }
        return $this->sessions[$player->getName()]["afk"]["kick-taskID"];
    }

    /**  ____             _
     *  |  _ \           | |
     *  | |_) | __ _  ___| | __
     *  |  _ < / _` |/ __| |/ /
     *  | |_) | (_| | (__|   <
     *  |____/ \__,_|\___|_|\_\
     */

    /**
     * @param Player $player
     * @return bool|Position
     */
    public function getLastPlayerPosition(Player $player){
        $session = $this->sessions[$player->getName()]["back"]["position"];
        if(!isset($session) || $session === false){
            return false;
        }
        return $session;
    }

    /**
     * @param Player $player
     * @return bool|array
     */
    public function getLastPlayerRotation(Player $player){
        $session = $this->sessions[$player->getName()]["back"]["rotation"];
        if(!isset($session) || $session === false){
            return false;
        }
        return $session;
    }

    /**
     * @param Player $player
     * @param Position $pos
     * @param int $yaw
     * @param int $pitch
     */
    public function setPlayerLastPosition(Player $player, Position $pos, $yaw, $pitch){
        $this->sessions[$player->getName()]["back"]["position"] = $pos;
        $this->sessions[$player->getName()]["back"]["rotation"] = [$yaw, $pitch];
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function returnPlayerToLastKnownPosition(Player $player){
        $pos = $this->getLastPlayerPosition($player);
        $rotation = $this->getLastPlayerRotation($player);
        if(!$pos instanceof Position || !is_array($rotation)){
            return false;
        }
        $yaw = $rotation[0];
        $pitch = $rotation[1];
        $player->teleport($pos, $yaw, $pitch);
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
        if($home === null || $home === "" || $home === " "){
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
            if(!is_array($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()])){
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
     * @return bool|array
     */
    public function getPowerToolItemCommand(Player $player, Item $item){
        if(!isset($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()]) || is_array($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()])){
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
     */
    public function setPowerToolItemCommands(Player $player, Item $item, array $commands){
        if($item->getID() !== 0){
            $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()] = $commands;
        }
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
        if(!isset($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()]) || !is_array($this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()])){
            return false;
        }
        return $this->sessions[$player->getName()]["powertool"]["commands"][$item->getID()];
    }

    /**
     *
     * Let you remove 1 command of the item command list
     * [if there're more than 1)
     * @param Player $player
     * @param Item $item
     * @param string $command
     */
    public function removePowerToolItemCommand(Player $player, Item $item, $command){
        if(($commands = $this->getPowerToolItemCommands($player, $item)) !== false){
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
     */
    public function setPowerToolItemChatMacro(Player $player, Item $item, $chat_message){
        if($item->getID() === 0){
            return;
        }
        $chat_message = str_replace("\\n", "\n", $chat_message);
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

    /**  _______ _____  _____                           _
     *  |__   __|  __ \|  __ \                         | |
     *     | |  | |__) | |__) |___  __ _ _   _  ___ ___| |_ ___
     *     | |  |  ___/|  _  // _ \/ _` | | | |/ _ / __| __/ __|
     *     | |  | |    | | \ |  __| (_| | |_| |  __\__ | |_\__ \
     *     |_|  |_|    |_|  \_\___|\__, |\__,_|\___|___/\__|___/
     *                                | |
     *                                |_|
     */

    public function hasARequest(Player $player){
        //Return the name of the requester
        //Ex: $name -> $player
    }

    public function madeARequest(Player $player){
        //Return the name of the player that requested to
        //Ex: $player -> $name
    }

    public function requestTPTo(Player $requester, Player $target){
        //Make a tp request to bring $requester to the position of $target
    }

    public function requestTPHere(Player $requester, Player $target){
        //Make a tp request to bring $target to the position of $requester
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
                if($p->getName() !== $player->getName()){
                    $p->showPlayer($player);
                }
            }
        }else{
            foreach($this->getServer()->getOnlinePlayers() as $p){
                if($p->getName() !== $player->getName()){
                    $p->hidePlayer($player);
                }
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
                $p->hidePlayer($player);
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
