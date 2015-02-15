<?php
namespace EssentialsPE\Commands\Override;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\RemoteConsoleCommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Msg extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "Msg", "Send private messages to other players", "/msg <player> <message ...>", ["tell", "m", "t", "whisper", "w"]);
        $this->setPermission("essentials.msg");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(count($args) < 2){
            $sender->sendMessage(TextFormat::RED . ($sender instanceof Player ? "" : "Usage: ") . $this->getUsage());
            return false;
        }
        $t = array_shift($args);
        if(strtolower($t) !== "console" && strtolower($t) !== "rcon"){
            $t = $this->getPlugin()->getPlayer($t);
            if(!$t){
                $sender->sendMessage(TextFormat::RED . "[Error] Player not found");
                return false;
            }
        }
        $sender->sendMessage(TextFormat::YELLOW . "[me -> " . ($t instanceof Player ? $t->getDisplayName() : $t) . "]" . TextFormat::RESET . " " . implode(" ", $args));
        $m = TextFormat::YELLOW . "[" . ($sender instanceof Player ? $sender->getDisplayName() : $sender->getName()) . " -> me]" . TextFormat::RESET . " " . implode(" ", $args);
        if($t instanceof Player){
            $t->sendMessage($m);
        }else{
            $this->getPlugin()->getLogger()->info($m);
        }
        $this->getPlugin()->setQuickReply(($t instanceof Player ? $t : ($t === "console" ? new ConsoleCommandSender() : new RemoteConsoleCommandSender())), $sender);
        return true;
    }
}
