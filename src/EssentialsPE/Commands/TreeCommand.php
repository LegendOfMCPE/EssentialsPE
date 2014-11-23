<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\block\Sapling;
use pocketmine\command\CommandSender;
use pocketmine\level\generator\object\Tree;
use pocketmine\Player;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;

class TreeCommand extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "tree", "Spawns a tree", "/tree <type>");
        $this->setPermission("essentials.tree");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        if(count($args) !== 1){
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
            return false;
        }
        $transparent = [];
		// [EXCEPTION_INFO] Only variables should be passed by reference in PocketMine-MP\src\pocketmine\entity\Living.php on line 237
        $block = $sender->getTargetBlock(100, $transparent);
        while(!$block->isSolid){
            if($block === null){
                break;
            }
            $transparent[] = $block->getID();
            $block = $sender->getTargetBlock(100, $transparent);
        }
        if($block === null){
            $sender->sendMessage(TextFormat::RED . "There isn't a reachable block");
            return false;
        }
        switch(strtolower($args[0])){
            case "tree":
                $type = Sapling::OAK;
                break;
            case "birch":
                $type = Sapling::BIRCH;
                break;
            case "redwood":
                $type = Sapling::SPRUCE;
                break;
            case "jungle":
                $type = Sapling::JUNGLE;
                break;
            /*case "redmushroom":
                $type = Sapling::RED_MUSHROOM;
                break;
            case "brownmushroom":
                $type = Sapling::BROWN_MUSHROOM;
                break;
            case "swamp":
                $type = Sapling::SWAMP;
                break;*/
            default:
                $sender->sendMessage(TextFormat::RED . "Invalid tree type, try with:\n<tree|birch|redwood|jungle>");
                return false;
                break;
        }
        $tree = new Tree();
        $tree->growTree($sender->getLevel(), $block->getFloorX(), $block->getFloorY(), $block->getFloorZ(), new Random(mt_rand()), $type);
        $sender->sendMessage(TextFormat::GREEN . "Tree spawned!");
        return true;
    }
} 