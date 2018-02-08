<?php

declare(strict_types = 1);

namespace EssentialsPE\EventHandlers;

use EssentialsPE\BaseFiles\BaseEventHandler;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\lang\TextContainer;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PlayerEvents extends BaseEventHandler{
    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event): void{
        // Ban remove:
        if($event->getPlayer()->isBanned() && $event->getPlayer()->hasPermission("essentials.ban.exempt")){
            $event->getPlayer()->setBanned(false);
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void{
        // Player session creation:
        $this->getAPI()->createSession($event->getPlayer());
        $message = $event->getJoinMessage();
        if($message instanceof TranslationContainer){
            foreach($message->getParameters() as $i => $m){
                $message->setParameter($i, str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $m));
            }
        }elseif($message instanceof TextContainer){
            $message->setText(str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $message->getText()));
        }else{
            $message = str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $message);
        }
        $event->setJoinMessage($message);

        // Hide vanished players with "noPacket"
        foreach($event->getPlayer()->getServer()->getOnlinePlayers() as $p){
            if($this->getAPI()->isVanished($p) && $this->getAPI()->hasNoPacket($p)){
                $event->getPlayer()->hidePlayer($p);
            }
        }
        $i = $this->getAPI()->getMutedUntil($event->getPlayer());
        if($i instanceof \DateTime && $event->getPlayer()->hasPermission("essentials.mute.notify")){
            $event->getPlayer()->sendMessage(TextFormat::YELLOW . "Remember that you're muted until " . TextFormat::AQUA . $i->format("l, F j, Y") . TextFormat::YELLOW . " at " . TextFormat::AQUA . $i->format("h:ia"));
        }
        //$this->getAPI()->setPlayerBalance($event->getPlayer(), $this->getAPI()->getDefaultBalance()); TODO
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void{
        // Quit message (nick):
        $message = $event->getQuitMessage();
        if($message instanceof TranslationContainer){
            foreach($message->getParameters() as $i => $m){
                $message->setParameter($i, str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $m));
            }
        }elseif($message instanceof TextContainer){
            $message->setText(str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $message->getText()));
        }else{
            $message = str_replace($event->getPlayer()->getName(), $event->getPlayer()->getDisplayName(), $message);
        }
        $event->setQuitMessage($message);

        // Session destroy:
        if($this->getAPI()->sessionExists($event->getPlayer())){
            $this->getAPI()->removeSession($event->getPlayer());
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event): void{
        if($this->getAPI()->isMuted($event->getPlayer())){
            if($event->getPlayer()->hasPermission("essentials.mute.exempt")){
                $this->getAPI()->setMute($event->getPlayer(), false, null, false);
            }elseif(($t = $this->getAPI()->getMutedUntil($event->getPlayer())) === null){
                $event->setCancelled(true);
            }else{
                $t2 = new \DateTime();
                if($t < $t2){
                    $this->getAPI()->setMute($event->getPlayer(), false, null, false);
                }else{
                    $event->setCancelled(true);
                }
            }
        }elseif($this->getAPI()->isAFK($event->getPlayer())){
            $this->getAPI()->setAFKMode($event->getPlayer(), false, true);
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event): void{
        $command = $this->getAPI()->colorMessage($event->getMessage(), $event->getPlayer());
        if($command === false){
            $event->setCancelled(true);
        }
        $event->setMessage($command);
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event): void{
        $entity = $event->getPlayer();
        if($this->getAPI()->isAFK($entity)){
            $this->getAPI()->setAFKMode($entity, false, true);
        }

        $this->getAPI()->setLastPlayerMovement($entity, time());
    }

    /**
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->getAPI()->setPlayerLastPosition($entity, $entity->getLocation());
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     *
     * @priority MONITOR
     */
    public function onEntityLevelChange(EntityLevelChangeEvent $event): void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->getAPI()->switchLevelVanish($entity, $event->getOrigin(), $event->getTarget());
        }
    }

    /**
     * @param PlayerBedEnterEvent $event
     */
    public function onPlayerSleep(PlayerBedEnterEvent $event): void{
        if($event->getPlayer()->hasPermission("essentials.home.bed")){
            $this->getAPI()->setHome($event->getPlayer(), "bed", $event->getPlayer()->getPosition());
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGH
     */
    public function onEntityDamageByEntity(EntityDamageEvent $event): void{
        $victim = $event->getEntity();
        if($victim instanceof Player){
            if($this->getAPI()->isGod($victim) || ($this->getAPI()->isAFK($victim) && $this->getPlugin()->getConfig()->getNested("afk.safe"))){
                $event->setCancelled(true);
            }

            if($event instanceof EntityDamageByEntityEvent){
                $issuer = $event->getDamager();
                if($issuer instanceof Player){
                    if(!($s = $this->getAPI()->isPvPEnabled($issuer)) || !$this->getAPI()->isPvPEnabled($victim)){
                        $issuer->sendMessage(TextFormat::RED . (!$s ? "You have" : $victim->getDisplayName() . " has") . " PvP disabled!");
                        $event->setCancelled(true);
                    }

                    if($this->getAPI()->isGod($issuer) && !$issuer->hasPermission("essentials.god.pvp")){
                        $event->setCancelled(true);
                    }

                    if($this->getAPI()->isVanished($issuer) && !$issuer->hasPermission("essentials.vanish.pvp")){
                        $event->setCancelled(true);
                    }
                }
            }
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void{
        if($event->getEntity()->hasPermission("essentials.back.ondeath")){
            $this->getAPI()->setPlayerLastPosition($event->getEntity(), $event->getEntity()->getLocation());
        }else{
            $this->getAPI()->removePlayerLastPosition($event->getEntity());
        }
    }
}
