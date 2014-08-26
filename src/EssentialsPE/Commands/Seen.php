<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Seen extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "seen", "See player's last played time", "/seen <player>");
        $this->setPermission("essentials.seen");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) != 1){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
        }else{
            $player = $this->getAPI()->getPlayer($args[0]);
            if($player != false){
                $sender->sendMessage(TextFormat::GREEN . $player->getDisplayName() . " is online!");
            }else{
                if(!is_numeric($sender->getServer()->getOfflinePlayer($args[0])->getLastPlayed())){
                    $sender->sendMessage(TextFormat::RED . "$args[0] never played on this server.");
                }else{
                    $player = $sender->getServer()->getOfflinePlayer($args[0])->getLastPlayed() / 1000;
                    $current = time();
                    if(date("Y", $player) == date("Y", $current)){ //Year (Ex. "2014")
                        if(date("n", $player) == date("n", $current)){ //Month in numbers (1 - 12)
                            if(date("W", $player) == date("W", $current)){ //Week of the year (Ex. the week #42 of the year)
                                if(date("j", $player) == date("j", $current)){ //Day Number (1 - 30/31)
                                    if(date("G", $player) == date("G", $current)){ //Hour (24 hour format, Ex. 1 - 24)
                                        if(date("i", $player) == date("i", $current)){ //Minute (1 - 60)
                                            $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen a moment ago.");
                                        }else{
                                            if(date("i", $current) - date("i", $player) == 1){
                                                $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen a minute ago.");
                                            }else{
                                                $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . (date("i", $current) - date("i", $player)) . " minutes ago.");
                                            }
                                        }
                                    }else{
                                        if(date("G", $current) - date("G", $player) == 1){
                                            $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen an hour ago.");
                                        }else{
                                            $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . (date("G", $current) - date("G", $player)) . " hours ago.");
                                        }
                                    }
                                }else{
                                    if(date("j", $current) - date("j", $player) == 1){
                                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen yesterday.");
                                    }else{
                                        $sender->sendMessage(TextFormat::YELLOW . "$args[0] was last seen " . (date("j", $current) - date("j", $player)) . "days ago.");
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
    }
}
