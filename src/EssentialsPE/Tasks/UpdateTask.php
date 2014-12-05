<?php
namespace EssentialsPE\Tasks;

use EssentialsPE\Loader;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class UpdateTask extends AsyncTask{
	/** @var \EssentialsPE\UpdateChecker[] */
	private $checkers;
	/** @var CommandSender */
	private $recipient;
	/** @var Loader */
	private $loader;
	public function __construct($checkers, CommandSender $r, Loader $l){
		$this->checkers = $checkers;
		$this->recipient = $r;
		$this->loader = $l;
	}
	public function onRun(){
		$runs = [];
		foreach($this->checkers as $checker){
			$checker->check($runs);
		}
		$this->setResult($runs);
	}
	public function onCompletion(Server $server){
		foreach($this->getResult() as $run){
			call_user_func($run, $this->recipient, $this->loader);
		}
	}
}
