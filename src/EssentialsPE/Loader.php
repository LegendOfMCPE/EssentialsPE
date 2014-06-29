<?php
namespace EssentialsPE;

//Events:
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\level;
use pocketmine\level\Explosion;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class Loader extends PluginBase implements Listener{
    public $dir = "plugins/Essentials/";

    //Sessions:
    public $defaults = [
        "mute" => false,
        "vanish" => false
    ];
    public $sessions = [];

    //Configs:
    public $nicks;

    public function onEnable() {
        @mkdir($this->dir);
	    $this->getLogger()->info(TextFormat::YELLOW . "Loading...");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $alias, array $args){
        $usage = TextFormat::RED . "Usage: " . $command->getUsage();
        $noperm = TextFormat::RED . $command->getPermissionMessage();
        $runingame = TextFormat::RED . "Please run this command in-game.";
        $notfound = TextFormat::RED . "[Error] Player not found.";
        switch($command->getName()){
            case "broadcast":
                if(count($args) == 0){
                    $sender->sendMessage($usage);
                }else{
                    $message = TextFormat::LIGHT_PURPLE . "[Broadcast] " . TextFormat::RESET . implode(" ",$args);
                    if(stripos($message, "p:") != false){
                        if(!$sender->hasPermission("essentials.command.broadcast.permselect")){
                            $sender->sendMessage($noperm);
                            return false;
                        }else{
                            $pos = stripos($message, "p:");
                            $permission = substr($message, $pos);
                            $message = substr_replace($message, "", $pos);
                            Server::getInstance()->broadcast($message, str_replace("p:", "", $permission));
                        }
                    }else{
                        Server::getInstance()->broadcastMessage($message);
                    }
                }
                return true;
                break;
            case "burn":
                if(count($args) != 2){
                    $sender->sendMessage($usage);
                }else{
                    $player = $this->getPlayer($args[0]);
                    $time = $args[1];
                    if($player == false){
                        $sender->sendMessage($notfound);
                    }else{
                        if(!is_numeric($time)){
                            $sender->sendMessage(TextFormat::RED . "[Error] Invalid time.");
                        }else{
                            $player->setOnFire($time);
                            $sender->sendMessage(TextFormat::YELLOW . "$args[0] is now on fire!");
                        }
                    }
                }
                return true;
                break;
                 case "explode":
                if(count($args) != 2){
                    $sender->sendMessage($usage);
                }else{
                    $player = $this->getPlayer($args[0]);
                    $explosionpower = $args[1];
                    if($player == false){
                        $sender->sendMessage($notfound);
                    }else{
                        if(!is_numeric($explosionpower)){
                            $sender->sendMessage(TextFormat::RED . "[Error] Invalid time.");
                        }else{
                            $explosion = new Explosion(new Position($player->x, ($entity->y +1), $player->z, $player->getLevel()), $explosionpower);
                $explosion->explode();
                            $sender->sendMessage(TextFormat::YELLOW . "$args[0] exploded!");
                        }
                    }
                }
                return true;
                break;
            case "clearinventory":
                if(count($args) > 1){
                    $sender->sendMessage($usage);
                }
                switch(count($args)){
                    case 0:
                        if(!$sender instanceof Player){
                            $sender->sendMessage($runingame);
                        }else{
                            $sender->getInventory()->clearAll();
                            $sender->sendMessage(TextFormat::AQUA . "Your inventory was cleared");
                        }
                        break;
                    case 1:
                        if(!$sender->hasPermission("essentials.command.clearinventory.other")){
                            $sender->sendMessage($noperm);
                            return false;
                        }else{
                            $player = $this->getPlayer($args[0]);
                            if($player == false){
                                $sender->sendMessage($notfound);
                            }else{
                                $player->getInventory()->clearAll();
                                $player->sendMessage(TextFormat::AQUA . "Your inventory was cleared");
                                if(substr($player->getDisplayName(), -1, 1) != "s"){
                                    $sender->sendMessage(TextFormat::GREEN . "$args[0]'s inventory was cleared");
                                }else{
                                    $sender->sendMessage(TextFormat::GREEN . "$args[0]' inventory was cleared");
                                }
                            }
                        }
                        break;
                }
                return true;
                break;
            case "essentials":
                $sender->sendMessage(TextFormat::YELLOW . "You're using " . TextFormat::AQUA . "EssentialsPE " . TextFormat::GREEN . "v" . Server::getInstance()->getPluginManager()->getPlugin("EssentialsPE")->getDescription()->getVersion());
                return true;
                break;
            case "extinguish":
                if(count($args) > 1){
                    $sender->sendMessage($usage);
                }
                switch(count($args)){
                    case 0:
                        if(!$sender instanceof Player){
                            $sender->sendMessage($usage);
                        }else{
                            $sender->extinguish();
                            $sender->sendMessage(TextFormat::AQUA . "You were extinguished!");
                        }
                        break;
                    case 1:
                        if(!$sender->hasPermission("essentials.command.extinguish.other")){
                            $sender->sendMessage($noperm);
                        }else{
                            $player = $this->getPlayer($args[0]);
                            if($player == false){
                                $sender->sendMessage($notfound);
                            }else{
                                $player->extinguish();
                                $sender->sendMessage(TextFormat::AQUA . "$args[0] has been extinguished!");
                            }
                        }
                        break;
                }
                return true;
                break;
            case "getpos":
                if(count($args) > 1){
                    $sender->sendMessage($usage);
                }
                switch(count($args)){
                    case 0:
                        if(!$sender instanceof Player){
                            $sender->sendMessage($usage);
                        }else{
                            $pos = $sender->getPosition();
                            $sender->sendMessage(TextFormat::GREEN . "You're in world: " . TextFormat::AQUA . $sender->getLevel()->getName() . "\n" . TextFormat::GREEN . "Your Coordinates are: X: " . TextFormat::AQUA . floor($pos->x) . TextFormat::GREEN . ", Y: " . TextFormat::AQUA . floor($pos->y) . TextFormat::GREEN . ", Z: " . TextFormat::AQUA . floor($pos->z));
                        }
                        break;
                    case 1:
                        if(!$sender->hasPermission("essentials.command.getpos.other")){
                            $sender->sendMessage($noperm);
                        }else{
                            $player = $this->getPlayer($args[0]);
                            if($player == false){
                                $sender->sendMessage($notfound);
                            }else{
                                $pos = $player->getPosition();
                                $sender->sendMessage(TextFormat::YELLOW . $args[0] . TextFormat::GREEN . " is in world: " . TextFormat::AQUA . $player->getLevel()->getName() . "\n" . TextFormat::GREEN . "Coordinates: X: " . TextFormat::AQUA . floor($pos->x) . TextFormat::GREEN . ", Y: " . TextFormat::AQUA . floor($pos->y) . TextFormat::GREEN . ", Z: " . TextFormat::AQUA . floor($pos->z));
                            }
                        }
                        break;
                }
                return true;
                break;
            case "heal":
                if(count($args) > 1){
                    $sender->sendMessage($usage);
                }
                switch(count($args)){
                    case 0:
                        if(!$sender instanceof Player){
                            $sender->sendMessage($usage);
                        }else{
                            $sender->setHealth($sender->getMaxHealth());
                            $sender->sendMessage(TextFormat::GREEN . "You have been healed!");
                        }
                        break;
                    case 1:
                        if(!$sender->hasPermission("essentials.command.heal.other")){
                            $sender->sendMessage($noperm);
                        }else{
                            $player = $this->getPlayer($args[0]);
                            if($player == false){
                                $sender->sendMessage($notfound);
                            }else{
                                $player->setHealth($player->getMaxHealth());
                                $player->sendMessage(TextFormat::GREEN . "You have been healed!");
                                $sender->sendMessage(TextFormat::GREEN . "$args[0] has been healed!");
                            }
                        }
                        break;
                }
                return true;
                break;
            case "kickall":
                if(count($args) == 0){
                    $reason = "Unknown reason";
                }else{
                    $reason = implode(" ", $args);
                }
                foreach(Server::getInstance()->getOnlinePlayers() as $p){
                    if($p != $sender){
                        $p->kick($reason);
                    }
                }
                $sender->sendMessage(TextFormat::AQUA . "Kicked all the players!");
                return true;
                break;
            case "more":
                if(!$sender instanceof Player){
                    $sender->sendMessage($runingame);
                }else{
                    $inv = $sender->getInventory();
                    $item = $inv->getItemInHand();
                    $item->setCount($item->getMaxStackSize());
                    $inv->setItemInHand($item);
                }
                return true;
                break;
            case "mute":
                if(count($args) != 1){
                    $sender->sendMessage($usage);
                    return false;
                }
                $player = $this->getPlayer($args[0]);
                if($player == false){
                    $sender->sendMessage($notfound);
                }else{
                    if($player->hasPermission("essentials.command.mute.exempt")){
                        $sender->sendMessage(TextFormat::RED . "$args[0] can't be muted");
                        return false;
                    }
                    $this->switchMute($player);
                    if(!$this->isMuted($player)){
                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] has been unmuted!");
                    }else{
                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] has been muted!");
                    }
                }
                break;
            case "nick":
                if(count($args) == 0 || count($args) > 2){
                    $sender->sendMessage($usage);
                }else{
                    switch(count($args)){
                        case 1:
                            $nick = $args[0];
                            if(!$sender instanceof Player){
                                $sender->sendMessage(TextFormat::RED . "Usage: /nick <nick> <player>");
                            }else{
                                $this->setNick($sender, $nick);
                                $sender->sendMessage(TextFormat::YELLOW . "Your nick is now $nick");
                            }
                            break;
                        case 2:
                            if(!$sender->hasPermission("essentials.command.nick.other")){
                                $sender->sendMessage($noperm);
                            }else{
                                $nick = $args[0];
                                $player = $this->getPlayer($args[1]);
                                if($player == false){
                                    $sender->sendMessage($notfound);
                                }else{
                                    $player->sendMessage(TextFormat::YELLOW . "Your nick is now $nick");
                                    if(substr($player->getDisplayName(), -1, 1) != "s"){
                                        $sender->sendMessage(TextFormat::GREEN . "$args[1]' nick is now $nick");
                                    }else{
                                        $sender->sendMessage(TextFormat::GREEN . "$args[1]'s nick is now $nick");
                                    }
                                    $this->setNick($player, $nick, true);
                                }
                            }
                            break;
                    }
                }
                return true;
                break;
            case "realname":
                if(count($args) != 1){
                    $sender->sendMessage($usage);
                }else{
                    $player = $this->getPlayer($args[0]);
                    if($player == false){
                        $sender->sendMessage($notfound);
                    }else{
                        if(substr($args[0], -1, 1) == "s"){
                            $sender->sendMessage(TextFormat::YELLOW . "$args[0]' real name is: " . TextFormat::RESET . $player->getName());
                        }else{
                            $sender->sendMessage(TextFormat::YELLOW . "$args[0]'s real name is: " . TextFormat::RESET . $player->getName());
                        }
                    }
                }
                break;
            case "repair":
                if(!$sender instanceof Player){
                    $sender->sendMessage($runingame);
                }else{
                    $inv = $sender->getInventory();
                    $item = $inv->getItemInHand();
                    $item->setDamage(0);
                    $inv->setItemInHand($item);
                }
                return true;
                break;
            case "seen":
                if(count($args) != 1){
                    $sender->sendMessage($usage);
                }else{
                    $player = $this->getPlayer($args[0]);
                    if($player != false){
                        $sender->sendMessage(TextFormat::GREEN . $player->getDisplayName() . " is online!");
                    }else{
                        if(!is_numeric(Server::getInstance()->getOfflinePlayer($args[0])->getLastPlayed())){
                            $sender->sendMessage(TextFormat::RED . "$args[0] never played on this server.");
                        }else{
                            $player = Server::getInstance()->getOfflinePlayer($args[0])->getLastPlayed();
                            $current = time();
                            if(date("Y", $player) == date("Y", $current)){ //Year (Ex. "2014")
                                if(date("n", $player) == date("n", $current)){ //Month (January - December)
                                    if(date("W", $player) == date("W", $current)){ //Week of the year (Ex. the week #42 of the year)
                                        if(date("j", $player) == date("j", $current)){ //Day Number (1 - 30/31)
                                            if(date("G", $player) == date("G", $current)){ //Hour (24 hour format, Ex. 1 - 24)
                                                if(date("i", $player) == date("i", $current)){ //Minute (1 - 60)
                                                    $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen a moment ago.");
                                                }else{
                                                    if(date("i", $current) - date("i", $player) == 1){
                                                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . date("i", $current) - date("i", $player) . " minute ago.");
                                                    }else{
                                                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . date("i", $current) - date("i", $player) . " minutes ago.");
                                                    }
                                                }
                                            }else{
                                                if(date("G", $current) - date("G", $player) == 1){
                                                    $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen an hour ago.");
                                                }else{
                                                    $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . date("G", $current) - date("G", $player) . " hours ago.");
                                                }
                                            }
                                        }else{
                                            if(date("j", $current) - date("j", $player) == 1){
                                                $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen yesterday.");
                                            }else{
                                                $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . date("j", $current) - date("j", $player) . "days ago.");
                                            }
                                        }
                                    }else{
                                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen on " . date("l, F j", $player) . " at " . date("g:i a", $player));
                                    }
                                }else{
                                    $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen on " . date("l, F j", $player) . " at " . date("g:i a", $player));
                                }
                            }else{
                                $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen on " . date("l, F j", $player) . " of " . date("Y", $player) . " at " . date("g:i a", $player));
                            }
                        }
                    }
                }
                return true;
                break;
            case "setspawn":
                if(!$sender instanceof Player){
                    $sender->sendMessage($runingame);
                }else{
                    $pos = $sender->getPosition();
                    $sender->getLevel()->setSpawn($pos);
                    $this->getServer()->setDefaultLevel($sender->getLevel());
                    $sender->sendMessage(TextFormat::YELLOW . "Spawn changed!");
                }
                return true;
                break;
            case "top":
                if(!$sender instanceof Player){
                    $sender->sendMessage($runingame);
                }else{
                    $level = $sender->getLevel();
                    $block = $level->getHighestBlockAt($sender->getX(), $sender->getZ());
                    if($block instanceof Position || $block instanceof Vector3){
                        $sender->sendMessage(TextFormat::YELLOW . "Teleporting...");
                        $sender->teleport($block);
                    }
                }
                return true;
                break;
            case "vanish":
                if(count($args) > 1){
                    $sender->sendMessage($usage);
                }else{
                    switch(count($args)){
                        case 0:
                            if(!$sender instanceof Player){
                                $sender->sendMessage($usage);
                            }else{
                                $this->switchVanish($sender);
                                if(!$this->isVanished($sender)){
                                    $sender->sendMessage(TextFormat::GRAY . "You're now visible");
                                }else{
                                    $sender->sendMessage(TextFormat::GRAY . "You're now vanished!");
                                }
                            }
                            break;
                        case 1:
                            $player = $this->getPlayer($args[0]);
                            if($player == false){
                                $sender->sendMessage($notfound);
                            }else{
                                $this->switchVanish($player);
                                if(!$this->isVanished($player)){
                                    $player->sendMessage(TextFormat::GRAY . "You're now visible");
                                    $sender->sendMessage(TextFormat::GRAY . "$args[0] is now visible");
                                }else{
                                    $player->sendMessage(TextFormat::GRAY . "You're now vanished!");
                                    $sender->sendMessage(TextFormat::GRAY . "$args[0] is now vanished!");
                                }
                            }
                            break;
                    }
                }
                return true;
                break;

            //Ban exempt TODO
            case "ban":
            case "ban-ip":
                $player = $this->getPlayer($args[0]);
                if($player->hasPermission("essentials.command.ban.exempt")){
                    return false;
                }
                return true;
                break;
        }
        return true;
    }

    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority HIGHEST
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        //Ban remove:
        $player = $event->getPlayer();
        if($player->isBanned() && $player->hasPermission("essentials.command.ban.exempt")){
            $player->setBanned(false);
        }
        //Nick and NameTags:
        $nick = new Config("plugins/Essentials/Nicks.yml", Config::YAML);
        if($nick->exists($player->getName())){
            $this->setNick($player, $nick->get($player->getName()));
        }
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority HIGH
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        //Sessions:
        $this->createSession($player);
        //Join Message:
        $event->setJoinMessage($player->getDisplayName() . " joined the game");
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority HIGH
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        //Sessions:
        $this->destroySession($player);
        //Quit message (nick):
        $event->setQuitMessage($player->getDisplayName() . " left the game");
    }

    /**
     * @param PlayerChatEvent $event
     * @return bool
     *
     * @priority HIGH
     * @ignoreCancelled false
     */
    public function onPlayerChat(PlayerChatEvent $event){
        if($this->getSession($event->getPlayer(), "mute") == true){
            $event->setCancelled();
        }
    }

    public function getPlayer($player){
        $r = "";
        foreach(Server::getInstance()->getOnlinePlayers() as $p){
            if($p->getDisplayName()||$p->getName() == $player){
                $r = Server::getInstance()->getPlayerExact($p->getName());
            }
        }
        if($r == ""){
            return false;
        }else{
            return $r;
        }
    }

    //Sessions:
    public function createSession(Player $player){
        $player = $player->getName();
        if(!isset($this->sessions["$player"])){
            $this->sessions["$player"] = $this->defaults;
        }
        return true;
    }

    public function destroySession(Player $player){
        $player = $player->getName();
        if(isset($this->sessions["$player"])){
            unset($this->sessions["$player"]);
        }
        return true;
    }

    public function getSession(Player $player, $key){
        $player = $player->getName();
        if(!isset($this->sessions["$player"]) || !isset($this->sessions["$player"]["$key"])){
            return false;
        }else{
            return $this->sessions["$player"]["$key"];
        }
    }

    public function setSession(Player $player, $key, $value){
        $player = $player->getName();
        if(!isset($this->sessions["$player"]) || !isset($this->sessions["$player"]["$key"])){
            return false;
        }else{
            $this->sessions["$player"]["$key"] = $value;
            return true;
        }
    }

    //Mute:
    public function switchMute(Player $player){
        if(!$this->getSession($player, "mute")){
            return false;
        }else{
            if($this->getSession($player, "mute") == false){
                $this->setSession($player, "mute", true);
            }else{
                $this->setSession($player, "mute", false);
            }
            return true;
        }
    }

    public function isMuted(Player $player){
        if($this->getSession($player, "mute") == false){
            return false;
        }else{
            return true;
        }
    }

    //Nick:
    public function setNick(Player $player, $nick, $save = false){
        $player->setNameTag($nick);
        $player->setDisplayName($nick);
        $config = new Config("plugins/Essentials/Nicks.yml", Config::YAML);
        if($save == true){
            $config->set($player->getName(), $nick);
            $config->save();
        }
        return true;
    }

    //Vanish:
    public function switchVanish(Player $player){
        if(!$this->getSession($player, "vanish")){
            return false;
        }else{
            if($this->getSession($player, "vanish") == false){
                $this->setSession($player, "vanish", true);
                foreach($this->getServer()->getOnlinePlayers() as $p){
                    if($p != $player){
                        $p->hidePlayer($player);
                    }
                }
            }else{
                $this->setSession($player, "vanish", false);
                foreach($this->getServer()->getOnlinePlayers() as $p){
                    if($p != $player){
                        $p->hidePlayer($player);
                    }
                }
            }
            return true;
        }
    }

    public function isVanished(Player $player){
        if($this->getSession($player, "vanish") == false){
            return false;
        }else{
            return true;
        }
    }
}
