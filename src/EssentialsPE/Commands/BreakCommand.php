<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\block\Air;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BreakCommand extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "break", "Breaks the block you're looking at", "/break");
        $this->setPermission("essentials.break");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        if(count($args) !== 0){
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return false;
        }
        $transparent = [0, 7, 8, 9, 10];
		// [EXCEPTION_INFO] Only variables should be passed by reference in PocketMine-MP\src\pocketmine\entity\Living.php on line 237
        $block = $sender->getTargetBlock(100, $transparent);
        if($block === null){
            $sender->sendMessage(TextFormat::RED . "There isn't a reachable block");
            return false;
        }
        if($block->getID() === 7 && !$sender->hasPermission("essentials.break.bedrock")){
            $sender->sendMessage(TextFormat::RED . "You can't break bedrock");
            return false;
        }
        /*$sender->getLevel()->useBreakOn(new Vector3($block->getX(), $block->getY(), $block->getZ()));
        $sender->getLevel()->useBreakOn($block);*/
        $sender->getLevel()->setBlock($block, new Air(), true, true);
        return true;
    }
} 