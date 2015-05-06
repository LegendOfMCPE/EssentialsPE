<?php

namespace EssentialsPE\EventHandlers;

use EssentialsPE\Loader;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PlayerEvents implements Listener{
    /** @var Loader */
    public $plugin;

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        // Ban remove:
        if($event->getPlayer()->isBanned() && $event->getPlayer()->hasPermission("essentials.ban.exempt")){
            $event->getPlayer()->setBanned(false);
        }
        // Session configure:
        $this->plugin->createSession($event->getPlayer());
        // Nick and NameTag set:
        $this->plugin->setNick($event->getPlayer(), $this->plugin->getNick($event->getPlayer()), false);
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        //EssentialsPE join messages + CustomAlerts fix
        if($this->plugin->getConfig()->getAll()["enable-join-leave-messages"] && !$this->plugin->getServer()->getPluginManager()->getPlugin("CustomAlerts")){
        // Nick and NameTag set:
        $event->setJoinMessage(str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $event->getJoinMessage()));
}
        // Hide vanished players | TODO: Remove
        /*foreach($event->getPlayer()->getServer()->getOnlinePlayers() as $p){
            if($this->plugin->isVanished($p)){
                $event->getPlayer()->hidePlayer($p);
            }
        }*/
        //$this->plugin->setPlayerBalance($event->getPlayer(), $this->plugin->getDefaultBalance()); TODO
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
          //EssentialsPE leave messages + CustomAlerts fix
        if($this->plugin->getConfig()->getAll()["enable-join-leave-messages"] && !$this->plugin->getServer()->getPluginManager()->getPlugin("CustomAlerts")){
        // Quit message (nick):
        $event->setQuitMessage(str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(),$event->getQuitMessage()));
}
        // Nick and NameTag restore:
        $this->plugin->setNick($event->getPlayer(), $event->getPlayer()->getName(), false);

        // Sessions
        if($this->plugin->sessionExists($event->getPlayer())){
            // Remove teleport requests
            $this->plugin->removeTPRequest($event->getPlayer());
            // Session destroy:
            $this->plugin->removeSession($event->getPlayer());
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event){
        if($this->plugin->isAFK($event->getPlayer())){
            $this->plugin->setAFKMode($event->getPlayer(), false, true);
        }
        if($this->plugin->isMuted($event->getPlayer())){
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event){
        $command = $this->plugin->colorMessage($event->getMessage(), $event->getPlayer());
        if($command === false){
            $event->setCancelled(true);
        }
        $event->setMessage($command);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        $entity = $event->getPlayer();
        if($this->plugin->isAFK($entity)){
            $this->plugin->setAFKMode($entity, false, true);
        }

        $this->plugin->setLastPlayerMovement($entity, time());
    }

    /**
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->plugin->setPlayerLastPosition($entity, $entity->getPosition(), $entity->getYaw(), $entity->getPitch());
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     *
     * @priority HIGHEST
     */
    public function onEntityLevelChange(EntityLevelChangeEvent $event){
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->plugin->switchLevelVanish($entity, $event->getOrigin(), $event->getTarget());
        }
    }

    /**
     * @param PlayerBedEnterEvent $event
     */
    public function onPlayerSleep(PlayerBedEnterEvent $event){
        if($event->getPlayer()->hasPermission("essentials.home.bed")){
            $this->plugin->setHome($event->getPlayer(), "bed", $event->getPlayer()->getPosition());
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGH
     */
    public function onEntityDamageByEntity(EntityDamageEvent $event){
        $victim = $event->getEntity();
        if($victim instanceof Player){
            if($this->plugin->isGod($victim) || ($this->plugin->isAFK($victim)) && $this->plugin->getConfig()->getNested("afk.safe")){
                $event->setCancelled(true);
            }

            if($event instanceof EntityDamageByEntityEvent){
                $issuer = $event->getDamager();
                if($issuer instanceof Player){
                    if(!($s = $this->plugin->isPvPEnabled($issuer)) || !$this->plugin->isPvPEnabled($victim)){
                        $issuer->sendMessage(TextFormat::RED . (!$s ? "You have" : $victim->getDisplayName() . " has") . " PvP disabled!");
                        $event->setCancelled(true);
                    }

                    if($this->plugin->isGod($issuer) && !$issuer->hasPermission("essentials.god.pvp")){
                        $event->setCancelled(true);
                    }

                    if($this->plugin->isVanished($issuer) && !$issuer->hasPermission("essentials.vanish.pvp")){
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event){
        if($event->getEntity()->hasPermission("essentials.back.ondeath")){
            $this->plugin->setPlayerLastPosition($event->getEntity(), $event->getEntity()->getPosition(), $event->getEntity()->getYaw(), $event->getEntity()->getPitch());
        }else{
            $this->plugin->removePlayerLastPosition($event->getEntity());
        }
    }
}
