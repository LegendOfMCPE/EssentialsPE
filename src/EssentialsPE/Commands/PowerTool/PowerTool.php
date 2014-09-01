<?php
namespace EssentialsPE\Commands\PowerTool;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PowerTool extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "powertool", "Toogle PowerTool on the item you're holding", "/powertool <command> <arguments...>", ["pt"]);
        $this->setPermission("essentials.powertool");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
            return false;
        }
        $item = $sender->getInventory()->getItemInHand();
        if($item->getID() == Item::AIR){
            $sender->sendMessage(TextFormat::RED . "You can't assign a command to an empty hand.");
            return false;
        }

        if(count($args) === 0){
            if(!$this->getPlugin()->getPowerToolItemCommand($sender, $item) || !$this->getPlugin()->getPowerToolItemChatMacro($sender, $item)){
                $sender->sendMessage(TextFormat::RED . $this->getUsage());
                return false;
            }
            if($this->getPlugin()->getPowerToolItemCommand($sender, $item) !== false){
                $sender->sendMessage(TextFormat::GREEN . "Command removed from this item.");
            }elseif($this->getPlugin()->getPowerToolItemCommands($sender, $item) !== false){
                $sender->sendMessage(TextFormat::GREEN . "Commands removed from this item.");
            }
            if($this->getPlugin()->getPowerToolItemChatMacro($sender, $item) !== false){
                $sender->sendMessage(TextFormat::GREEN . "Chat macro removed from this item.");
            }
            $this->getPlugin()->disablePowerToolItem($sender, $item);
        }else{
            if($args[0] == "pt" || $args[0] == "ptt" || $args[0] == "powertool" || $args[0] == "powertooltoggle"){
                $sender->sendMessage(TextFormat::RED . "This command can't be assigned");
                return false;
            }
            $command = implode(" ", $args);
            if(stripos($command, "c:") !== false){ //Create a chat macro
                $c = substr($command, 2);
                $this->getPlugin()->setPowerToolItemChatMacro($sender, $item, $c);
                $sender->sendMessage(TextFormat::GREEN . "Chat macro successfully assigned to this item!");
            }elseif(stripos($command, "a:") !== false){
                if(!$sender->hasPermission("essentials.powertool.append")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $commands = substr($command, 2);
                $commands = explode(";", $commands);
                $this->getPlugin()->setPowerToolItemCommands($sender, $item, $commands);
                $sender->sendMessage(TextFormat::GREEN . "Commands successfully assigned to this item!");
            }elseif(stripos($command, "r:") !== false){
                if(!$sender->hasPermission("essentials.powertool.append")){
                    $sender->sendMessage(TextFormat::RED . $this->getPermissionMessage());
                    return false;
                }
                $command = substr($command, 2);
                $this->getPlugin()->removePowerToolItemCommand($sender, $item, $command);
                $sender->sendMessage(TextFormat::YELLOW . "Command successfully removed from this item!");
            }elseif(count($args) === 1){
                switch(strtolower($args[0])){
                    case "l":
                        $commands = false;
                        if($this->getPlugin()->getPowerToolItemCommand($sender, $item) !== false){
                            $commands = $this->getPlugin()->getPowerToolItemCommand($sender, $item);
                        }elseif($this->getPlugin()->getPowerToolItemCommands($sender, $item) !== false){
                            $commands = $this->getPlugin()->getPowerToolItemCommand($sender, $item);
                        }
                        $list = "=== Command ===";
                        if($commands === false){
                            $list .= "\n" . TextFormat::ITALIC . "**There aren't any commands for this item**";
                        }else{
                            if(!is_array($commands)){
                                $list .= "\n* /$commands";
                            }else{
                                foreach($commands as $c){
                                    $list .= "\n* /$c";
                                }
                            }
                        }
                        $chat_macro = $this->getPlugin()->getPowerToolItemChatMacro($sender, $item);
                        $list .= "\n=== Chat Macro ===";
                        if($chat_macro === false){
                            $list .= "\n" . TextFormat::ITALIC . "**There aren't any chat macros for this item**";
                        }else{
                            $list .= "\n$chat_macro";
                        }
                        $list .= "\n=== End of the lists ===";
                        $sender->sendMessage($list);
                        return true;
                        break;
                    case "d":
                        if(!$this->getPlugin()->getPowerToolItemCommand($sender, $item)){
                            $sender->sendMessage(TextFormat::RED . $this->getUsage());
                            return false;
                        }
                        $this->getPlugin()->disablePowerToolItem($sender, $item);
                        $sender->sendMessage(TextFormat::GREEN . "Command removed from this item.");
                        return true;
                        break;
                }
            }else{
                $this->getPlugin()->setPowerToolItemCommand($sender, $item, $command);
                $sender->sendMessage(TextFormat::GREEN . "Command successfully assigned to this item!");
            }
        }
        return true;
    }
} 
