<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseFiles\BaseAPI;
use EssentialsPE\BaseFiles\BaseCommand;
use pocketmine\block\Sapling;
use pocketmine\command\CommandSender;
use pocketmine\level\generator\object\BigTree;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BigTreeCommand extends BaseCommand{
    /**
     * @param BaseAPI $api
     */
    public function __construct(BaseAPI $api){
        parent::__construct($api, "bigtree", "Spawns a big tree", "<tree|redwood|jungle>", false);
        $this->setPermission("essentials.bigtree");
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $this->sendUsage($sender, $alias);
            return false;
        }
        if(count($args) !== 1){
            $this->sendUsage($sender, $alias);
            return false;
        }
        #$transparent = [];
        $block = $sender->getTargetBlock(100, BaseAPI::NON_SOLID_BLOCKS);
        /*while(!$block->isSolid){
            if($block === null){
                break;
            }
            $transparent[] = $block->getID();
            $block = $sender->getTargetBlock(100, $transparent);
        }*/
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
        $tree->placeObject($sender->getLevel(), $block->getFloorX(), $block->getFloorY() + 1, $block->getFloorZ(), $type);
        $sender->sendMessage(TextFormat::GREEN . "BigTree spawned!");
        return true;
    }
} 