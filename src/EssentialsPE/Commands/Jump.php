<?php
namespace EssentialsPE\Commands;

use EssentialsPE\BaseCommand;
use EssentialsPE\Loader;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Jump extends BaseCommand{
    public function __construct(Loader $plugin){
        parent::__construct($plugin, "jump", "Teleport you to the block you're looking at", "/jump", ["j", "jumpto"]);
        $this->setPermission("essentials.jump");
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$this->testPermission($sender)){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Please run this command in-game");
            return false;
        }
        if(count($args) > 0){
            $sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
        }
        $vectors = $sender->getDirectionVector()->divide(4);;
        $pos = $sender->getPosition();
        $level = $sender->getLevel();
        $confirmedPosition = null;
        for($lastPos = null; true; $lastPos = $pos, $pos = $pos->add($vectors)){
            if($lastPos instanceof Vector3){
                if($lastPos->getFloorX() === $pos->getFloorX() and $lastPos->getFloorY() === $pos->getFloorY() and $lastPos->getFloorZ() === $pos->getFloorZ()){
                    continue;
                }
            }
            if($pos->y < 0){
                $sender->sendMessage("You can't jump into the void!");
                return true;
            }
            if($pos->y >= 128){
                $sender->sendMessage("You can't teleport into the space!");
            }
            $X = $pos->x >> 4;
            $Z = $pos->z >> 4;
            if(!$level->isChunkGenerated($X, $Z)){
                $level->generateChunk($X, $Z);
            }
            $block = $level->getBlock($pos->floor());
            if(!($block instanceof Block)){
                $sender->sendMessage("You can't teleport to that position."); // unknown error?
                return true;
            }
            if(!in_array($block->getID(), [Block::AIR, Block::WATER, Block::STILL_WATER, Block::LAVA, Block::STILL_LAVA])){
                $confirmedPosition = $pos->floor();
            }
        }
        $sender->sendMessage(TextFormat::YELLOW . "Teleporting...");
        $sender->teleport($confirmedPosition->add(0, 1));
        return true;
    }
}
