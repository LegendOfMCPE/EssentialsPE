<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\block\Sapling;
use pocketmine\command\CommandSender;
use pocketmine\level\generator\object\BigTree;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BigTreeCommand extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "bigtree", "Spawns a big tree", "/bigtree <tree|redwood|jungle");
        $this->setPermission("essentials.bigtree");
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
            case "redwood":
                $type = Sapling::SPRUCE;
                break;
            case "jungle":
                $type = Sapling::JUNGLE;
                break;
            default:
                $sender->sendMessage(TextFormat::RED . "Invalid tree type, try with:\n<tree|redwood|jungle>");
                return false;
                break;
        }
        $tree = new BigTree();
        $tree->placeObject($sender->getLevel(), $block->getFloorX(), ($block->getFloorY() + 1), $block->getFloorZ(), $type);
        $sender->sendMessage(TextFormat::GREEN . "Tree spawned!");
        return true;
    }
} 