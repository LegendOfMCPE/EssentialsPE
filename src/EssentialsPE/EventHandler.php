<?php
namespace EssentialsPE;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class EventHandler implements Listener{
    /** @var Loader */
    public $plugin;

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerPreLoginEvent $event
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        $player = $event->getPlayer();

        //Ban remove:
        if($player->isBanned() && $player->hasPermission("essentials.ban.exempt")){
            $player->setBanned(false);
        }
        //Nick and NameTag set:
        $this->plugin->setNick($player, $this->plugin->getNick($player), false);
    }

    /**
     * @param PlayerLoginEvent $event
     */
    public function onPlayerLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        //Session configure:
        $this->plugin->muteSessionCreate($player);
        $this->plugin->createSession($player);
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();

        //Nick and NameTag set:
        $event->setJoinMessage($player->getDisplayName() . " joined the game");
        //Hide vanished players
        foreach($player->getServer()->getOnlinePlayers() as $p){
            if($this->plugin->isVanished($p)){
                $player->hidePlayer($p);
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();

        //Quit message (nick):
        $event->setQuitMessage($player->getDisplayName() . " left the game");
        //Nick and NameTag restore:
        $this->plugin->setNick($player, $player->getName(), false);
        //Session destroy:
        $this->plugin->removeSession($player);
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority HIGH
     */
    public function onPlayerChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        if($this->plugin->isMuted($player)){
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     *
     * @priority HIGH
     */
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();

        $command = $this->plugin->colorMessage($event->getMessage(), $player);
        if($command === false){
            $event->setCancelled(true);
        }
        $event->setMessage($command);
    }

    /**
     * @param ServerCommandEvent $event
     */
    public function onServerCommand(ServerCommandEvent $event){
        $command = $this->plugin->colorMessage($event->getCommand());
        if($command === false){
            $event->setCancelled(true);
        }
        $event->setCommand($command);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        $entity = $event->getPlayer();
        if($this->plugin->isAFK($entity)){
            $this->plugin->setAFKMode($entity, false);
            $entity->sendMessage(TextFormat::GREEN . "You're no longer AFK");
            foreach($entity->getServer()->getOnlinePlayers() as $p){
                if($p !== $entity){
                    $p->sendMessage(TextFormat::GREEN . $entity->getDisplayName() . " is no longer AFK");
                }
            }
            $entity->getServer()->getLogger()->info(TextFormat::GREEN . $entity->getDisplayName() . " is no longer AFK");
        }
    }

    /**
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->plugin->setPlayerLastPosition($entity, $entity->getPosition(), $entity->yaw, $entity->pitch);
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     *
     * @priority HIGHEST
     */
    public function onEntityLevelChange(EntityLevelChangeEvent $event){
        $entity = $event->getEntity();
        $origin = $event->getOrigin();
        $target = $event->getTarget();
        if($entity instanceof Player){
            $this->plugin->switchLevelVanish($entity, $origin, $target);
        }
    }

    /**
     * @param PlayerBedEnterEvent $event
     */
    public function onPlayerSleep(PlayerBedEnterEvent $event){
        $player = $event->getPlayer();
        if($player->hasPermission("essentials.home.bed")){
            $this->plugin->setHome($player, "bed", new Position($player->getFloorX(), $player->getFloorY(), $player->getFloorZ(), $player->getLevel()));
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGH
     */
    public function onEntityDamageByEntity(EntityDamageEvent $event){
        $victim = $event->getEntity();
        if($event instanceof EntityDamageByEntityEvent){
            $issuer = $event->getDamager();
            if($victim instanceof Player && $issuer instanceof Player){
                if($this->plugin->isGod($victim)){
                    $event->setCancelled(true);
                }elseif($this->plugin->isGod($issuer) && !$issuer->hasPermission("essentials.god.pvp")){
                    $event->setCancelled(true);
                }

                if($this->plugin->isVanished($issuer) && !$issuer->hasPermission("essentials.vanish.pvp")){
                    $event->setCancelled(true);
                }

                if(!$this->plugin->isPvPEnabled($issuer)){
                    $issuer->sendMessage(TextFormat::RED . "You have PvP disabled!");
                    $event->setCancelled(true);
                }elseif(!$this->plugin->isPvPEnabled($victim)){
                    $issuer->sendMessage(TextFormat::RED . $victim->getDisplayName() . " has PvP disabled!");
                    $event->setCancelled(true);
                }
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * @return bool
     */
    public function onBlockTap(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();

        //PowerTool
        if($item->isPlaceable()){
            if($this->plugin->executePowerTool($player, $item)){
                $event->setCancelled(true);
            }
        }


        //Special Signs
        $perm = "essentials.sign.use";

        $tile = $block->getLevel()->getTile(new Vector3($block->getFloorX(), $block->getFloorY(), $block->getFloorZ()));
        if($tile instanceof Sign){
            $text = $tile->getText();
            $message = TextFormat::RED . "You don't have permissions to use this sign";

            //Free sign
            if($text[0] === "[Free]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "free")){
                    $player->sendMessage($message);
                }else{
                    if(($gm = $player->getServer()->getGamemodeString($player->getGamemode())) === "CREATIVE" || $gm === "SPECTATOR"){
                        $player->sendMessage(TextFormat::RED . "[Error] You're in " . strtolower($gm) . " mode");
                        return false;
                    }

                    $item_name = $text[1];
                    $damage = $text[2];

                    if(!is_numeric($item_name)){
                        $item = Item::fromString($item_name);
                    }else{
                        $item = Item::get($item_name);
                    }
                    $item->setDamage($damage);

                    $player->getInventory()->addItem($item);
                    $player->sendMessage(TextFormat::YELLOW . "Giving " . TextFormat::RED . $item->getCount() . TextFormat::YELLOW . " of " . TextFormat::RED .( $item->getName() === "Unknown" ? $item_name : $item->getName()));
                }
            }

            //Gamemode sign
            elseif($text[0] === "[Gamemode]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "gamemode")){
                    $player->sendMessage($message);
                }else{
                    if(($v = strtolower($text[1])) === "survival"){
                        $player->setGamemode(0);
                    }elseif($v === "creative"){
                        $player->setGamemode(1);
                    }elseif($v === "adventure"){
                        $player->setGamemode(2);
                    }elseif($v === "spectator"){
                        $player->setGamemode(3);
                    }
                }
            }

            //Heal sign
            elseif($text[0] === "[Heal]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "heal")){
                    $player->sendMessage($message);
                }else{
                    $player->heal($player->getMaxHealth());
                    $player->sendMessage(TextFormat::GREEN . "You have been healed!");
                }
            }

            //Repair sign
            elseif($text[0] === "[Repair]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "repair")){
                    $player->sendMessage($message);
                }else{
                    if(($v = $text[1]) === "Hand"){
                        if($this->plugin->isReparable($item = $player->getInventory()->getItemInHand())){
                            $item->setDamage(0);
                            $player->sendMessage(TextFormat::GREEN . "Item successfully repaired!");
                        }
                    }elseif($v === "All"){
                        foreach($player->getInventory()->getContents() as $item){
                            if($this->plugin->isReparable($item)){
                                $item->setDamage(0);
                            }
                        }
                        foreach($player->getInventory()->getArmorContents() as $item){
                            if($this->plugin->isReparable($item)){
                                $item->setDamage(0);
                            }
                        }
                        $player->sendMessage(TextFormat::GREEN . "All the tools on your inventory were repaired!" . TextFormat::AQUA . "\n(including the equipped Armor)");
                    }
                }
            }

            //Time sign
            elseif($text[0] === "[Time]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "time")){
                    $player->sendMessage($message);
                }else{
                    if(($v = $text[1]) === "Day"){
                        $player->getLevel()->setTime(0);
                        $player->sendMessage(TextFormat::GREEN . "Time set to \"Day\"");
                    }elseif($v === "Night"){
                        $player->getLevel()->setTime(12500);
                        $player->sendMessage(TextFormat::GREEN . "Time set to \"Night\"");
                    }
                }
            }

            //Teleport sign
            elseif($text[0] === "[Teleport]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "teleport")){
                    $player->sendMessage($message);
                }else{
                    $player->teleport(new Vector3($x = $text[1], $y = $text[2], $z = $text[3]));
                    $player->sendMessage(TextFormat::GREEN . "Teleporting to " . TextFormat::AQUA . $x . TextFormat::GREEN . ", " . TextFormat::AQUA . $y . TextFormat::GREEN . ", " . TextFormat::AQUA . $z);
                }
            }

            //Warp sign
            elseif($text[0] === "[Warp]"){
                $event->setCancelled(true);
                if(!$player->hasPermission($perm . "warp")){
                    $player->sendMessage($message);
                }else{
                    $warp = $this->plugin->getWarp($text[1]);
                    if(!$warp){
                        $player->sendMessage(TextFormat::RED . "[Error] Warp doesn't exists");
                        return false;
                    }
                    if(!$player->hasPermission("essentials.warps.*") && !$player->hasPermission("essentials.warps.$text[1]")){
                        $player->sendMessage(TextFormat::RED . "[Error] You can't teleport to that warp");
                        return false;
                    }
                    $player->teleport($warp[0], $warp[1], $warp[2]);
                    $player->sendMessage(TextFormat::GREEN . "Warping to $text[1]...");
                }
            }
        }
        return true;
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority HIGH
     */
    public function onBlockPlace(BlockPlaceEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();

        //PowerTool
        if($this->plugin->executePowerTool($player, $item)){
            $event->setCancelled(true);
        }

        //Unlimited block placing
        elseif($this->plugin->isUnlimitedEnabled($player)){
            $event->setCancelled(true);
            $pos = new Vector3($event->getBlockReplaced()->getX(), $event->getBlockReplaced()->getY(), $event->getBlockReplaced()->getZ());
            $player->getLevel()->setBlock($pos, $block, true);
        }
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority HIGH
     */
    public function onBlockBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();

        //Special Signs
        $perm = "essentials.sign.break.";

        $tile = $block->getLevel()->getTile(new Vector3($block->getFloorX(), $block->getFloorY(), $block->getFloorZ()));
        if($tile instanceof Sign){
            $text = $tile->getText();
            $message = TextFormat::RED . "You don't have permissions to break this sign";

            //Free sign
            if($text[0] === "[Free]" && !$player->hasPermission($perm . "free")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }

            //Gamemode sign
            elseif($text[0] === "[Gamemode]" && !$player->hasPermission($perm . "gamemode")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }

            //Heal sign
            elseif($text[0] === "[Heal]" && !$player->hasPermission($perm . "heal")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }

            //Repair sign
            elseif($text[0] === "[Repair]" && !$player->hasPermission($perm . "repair")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }

            //Time sign
            elseif($text[0] === "[Time]" && !$player->hasPermission($perm . "time")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }

            //Teleport sign
            elseif($text[0] === "[Teleport]" && !$player->hasPermission($perm . "teleport")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }

            //Warp sign
            elseif($text[0] === "[Warp]" && !$player->hasPermission($perm . "warp")){
                $event->setCancelled(true);
                $player->sendMessage($message);
            }
        }
    }

    /**
     * @param SignChangeEvent $event
     * @return bool
     */
    public function onSignChange(SignChangeEvent $event){
        $player = $event->getPlayer();
        //Colored Sign
        if($player->hasPermission("essentials.sign.color")){
            $event->setLine(0, $this->plugin->colorMessage($event->getLine(0)));
            $event->setLine(1, $this->plugin->colorMessage($event->getLine(1)));
            $event->setLine(2, $this->plugin->colorMessage($event->getLine(2)));
            $event->setLine(3, $this->plugin->colorMessage($event->getLine(3)));
        }

        //Special Signs
        $perm = "essentials.sign.create.";

        //Free sign
        if(strtolower($event->getLine(0)) === "[free]" && $player->hasPermission($perm . "free")){
            if(trim($event->getLine(1)) !== "" || $event->getLine(1) !== null){

                $item_name = $event->getLine(1);

                if(trim($event->getLine(2)) !== "" || $event->getLine(2) !== null){
                    $damage = $event->getLine(2);
                }else{
                    $damage = 0;
                }

                if(!is_numeric($item_name)){
                    $item = Item::fromString($item_name);
                }else{
                    $item = Item::get($item_name);
                }

                if($item->getID() === 0 || $item->getName() === "Air"){
                    $player->sendMessage(TextFormat::RED . "[Error] Invalid item name/ID");
                    $event->setCancelled(true);
                }else{
                    $player->sendMessage(TextFormat::GREEN . "Free sign successfully created!");
                    $event->setLine(0, "[Free]");
                    $event->setLine(1, ($item->getName() === "Unknown" ? $item->getID() : $item->getName()));
                    $event->setLine(2, $damage);
                }
            }else{
                $player->sendMessage(TextFormat::RED . "[Error] You should provide an item name/ID");
                $event->setCancelled(true);
            }
        }

        //Gamemode sign
        elseif(strtolower($event->getLine(0)) === "[gamemode]" && $player->hasPermission($perm . "gamemode")){
            switch(strtolower($event->getLine(1))){
                case "survival":
                case "0":
                    $event->setLine(1, "Survival");
                    break;
                case "creative":
                case "1":
                    $event->setLine(1, "Creative");
                    break;
                case "adventure":
                case "2":
                    $event->setLine(1, "Adventure");
                    break;
                case "spectator":
                case "view":
                case "3":
                    $event->setLine(1, "Spectator");
                    break;
                default:
                    $player->sendMessage(TextFormat::RED . "[Error] Unknown Gamemode, you should use \"Survival\", \"Creative\", \"Adventure\" or \"Spectator\"");
                    $event->setCancelled(true);
                    return false;
                    break;
            }
            $player->sendMessage(TextFormat::GREEN . "Gamemode sign successfully created!");
            $event->setLine(0, "[Gamemode]");
        }

        //Heal sign
        elseif(strtolower($event->getLine(0)) === "[heal]" && $player->hasPermission($perm . "heal")){
            $player->sendMessage(TextFormat::GREEN . "Heal sign successfully created!");
            $event->setLine(0, "[Heal]");
        }

        //Repair sign
        elseif(strtolower($event->getLine(0)) === "[repair]" && $player->hasPermission($perm . "repair")){
            switch(strtolower($event->getLine(1))){
                case "hand":
                    $event->setLine(1, "Hand");
                    break;
                case "all":
                    $event->setLine(1, "All");
                    break;
                default:
                    $player->sendMessage(TextFormat::RED . "[Error] Invalid argument, you should use \"Hand\" or \"All\"");
                    $event->setCancelled(true);
                    return false;
                    break;
            }
            $player->sendMessage(TextFormat::GREEN . "Repair sign successfully created!");
            $event->setLine(0, "[Repair]");
        }

        //Time sign
        elseif(strtolower($event->getLine(0)) === "[time]" && $player->hasPermission($perm . "time")){
            switch(strtolower($event->getLine(1))){
                case "day":
                    $event->setLine(1, "Day");
                    break;
                case "night";
                    $event->setLine(1, "Night");
                    break;
                default:
                    $player->sendMessage(TextFormat::RED . "[Error] Invalid time, you should use \"Day\" or \"Night\"");
                    $event->setCancelled(true);
                    return false;
                    break;
            }
            $player->sendMessage(TextFormat::GREEN . "Time sign successfully created!");
            $event->setLine(0, "[Time]");
        }

        //Teleport sign
        elseif(strtolower($event->getLine(0)) === "[teleport]" && $player->hasPermission($perm . "teleport")){
            if(!is_numeric($event->getLine(1))){
                $player->sendMessage(TextFormat::RED . "[Error] Invalid X position, Teleport sign will not work");
                $event->setCancelled(true);
            }elseif(!is_numeric($event->getLine(2))){
                $player->sendMessage(TextFormat::RED . "[Error] Invalid Y position, Teleport sign will not work");
                $event->setCancelled(true);
            }elseif(!is_numeric($event->getLine(3))){
                $player->sendMessage(TextFormat::RED . "[Error] Invalid Z position, Teleport sign will not work");
                $event->setCancelled(true);
            }else{
                $player->sendMessage(TextFormat::GREEN . "Teleport sign successfully created!");
                $event->setLine(0, "[Teleport]");
                $event->setLine(1, $event->getLine(1));
                $event->setLine(2, $event->getLine(2));
                $event->setLine(3, $event->getLine(3));
            }
        }

        //Warp sign
        elseif(strtolower($event->getLine(0)) === "[warp]" && $player->hasPermission($perm . "warp")){
            $warp = $event->getLine(1);
            if(!$this->plugin->warpExists($warp)){
                $player->sendMessage(TextFormat::RED . "[Error] Warp doesn't exists");
                $event->setCancelled(true);
            }else{
                $player->sendMessage(TextFormat::GREEN . "Warp sign successfully created!");
                $event->setLine(0, "[Warp]");
            }
        }
        return true;
    }

    /**
     * @param EntityExplodeEvent $event
     */
    public function onTNTExplode(EntityExplodeEvent $event){
        $tnt = $event->getEntity();
        if($tnt->namedtag->getName() === "EssNuke"){
            $event->setBlockList([]);
        }
    }
}