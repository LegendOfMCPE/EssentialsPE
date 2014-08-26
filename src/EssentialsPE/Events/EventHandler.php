<?php
namespace EssentialsPE\Events;

use EssentialsPE\Loader;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EventHandler implements Listener{
    /** @var \EssentialsPE\Loader  */
    public $api;

    public function __construct(Loader $plugin){
        $this->api = $plugin;
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
        $this->api->setNick($player, $this->api->getNick($player), false);
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();

        //Session configure:
        $this->api->muteSessionCreate($player);
        $this->api->createSession($player);
        //Nick and NameTag set:
        $event->setJoinMessage($player->getDisplayName() . " joined the game");
        //Hide vanished players
        foreach($player->getServer()->getOnlinePlayers() as $p){
            if($this->api->isVanished($p)){
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
        $this->api->setNick($player, $player->getName(), false);
        //Session destroy:
        $this->api->removeSession($player);
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority HIGH
     */
    public function onPlayerChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        if($this->api->isMuted($player)){
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
        $command = $event->getMessage();

        if((substr($command, 0, 4) === "/say" || substr($command, 0, 3) === "/me") && $this->api->isMuted($player)){
            $event->setCancelled(true);
        }

        $command = $this->api->colorMessage($event->getMessage(), $player);
        if($command === false){
            $event->setCancelled(true);
        }
        $event->setMessage($command);
    }

    /**
     * @param ServerCommandEvent $event
     */
    public function onServerCommand(ServerCommandEvent $event){
        $command = $this->api->colorMessage($event->getCommand());
        if($command === false){
            $event->setCancelled(true);
        }
        $event->setCommand($command);
    }

    /**
     * @param EntityMoveEvent $event
     */
    public function onEntityMove(EntityMoveEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Player){
            if($this->api->isAFK($entity)){
                $this->api->setAFKMode($entity, false);
                $entity->sendMessage(TextFormat::GREEN . "You're no longer AFK");
                foreach($entity->getServer()->getOnlinePlayers() as $p){
                    if($p !== $entity){
                        $p->sendMessage(TextFormat::GREEN . $entity->getDisplayName() . " is no longer AFK");
                        $entity->getServer()->getLogger()->info(TextFormat::GREEN . $entity->getDisplayName() . " is no longer AFK");
                    }
                }
            }
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
            $this->api->switchLevelVanish($entity, $origin, $target);
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGH
     */
    public function onEntityDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Player){
            if($this->api->isGod($entity)){
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     *
     * @priority HIGH
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
        $victim = $event->getEntity();
        $issuer = $event->getDamager();
        if($victim instanceof Player && $issuer instanceof Player){
            if($this->api->isGod($victim) || ($this->api->isAFK($victim) && $this->api->getConfig()->get("safe-afk") === true)){
                $event->setCancelled(true);
            }elseif($this->api->isGod($issuer) && !$issuer->hasPermission("essentials.god.pvp")){
                $event->setCancelled(true);
            }

            if(!$this->api->isPvPEnabled($issuer)){
                $issuer->sendMessage(TextFormat::RED . "You have PvP disabled!");
                $event->setCancelled(true);
            }
            if(!$this->api->isPvPEnabled($victim)){
                $issuer->sendMessage(TextFormat::RED . $victim->getDisplayName() . " have PvP disabled!");
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGH
     */
    public function onBlockTap(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();

        //PowerTool
        if($this->api->isPowerToolEnabled($player)){
            $event->setCancelled(true);
            $this->api->executePowerTool($player, $item);
        }
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
        if($this->api->isPowerToolEnabled($player)){
            $this->api->executePowerTool($player, $item);
            $event->setCancelled(true);
        }

        //Unlimited block placing
        elseif($this->api->isUnlimitedEnabled($player)){
            $pos = new Vector3($event->getBlockReplaced()->getX(), $event->getBlockReplaced()->getY(), $event->getBlockReplaced()->getZ());
            $player->getLevel()->setBlock($pos, $block, true);
            $event->setCancelled(true);
            //$player->getLevel()->setBlock($pos, $block, true);
        }
    }
}
