<?php
namespace EssentialsPE\Tasks;

use EssentialsPE\BaseFiles\BaseTask;
use EssentialsPE\BaseFiles\BaseAPI;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TPRequestTask extends BaseTask{
    /** @var Player  */
    protected $requester;

    /**
     * @param BaseAPI $api
     * @param Player $requester
     */
    public function __construct(BaseAPI $api, Player $requester){
        parent::__construct($api);
        $this->requester = $requester;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        if($this->requester instanceof Player && $this->requester->isOnline()) {
            $this->getAPI()->getServer()->getLogger()->debug(TextFormat::YELLOW . "Running EssentialsPE's TPRequestTask");
            $this->getAPI()->removeTPRequest($this->requester);
        }
    }
} 