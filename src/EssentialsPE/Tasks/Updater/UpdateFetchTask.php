<?php
namespace EssentialsPE\Tasks\Updater;

use EssentialsPE\Loader;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class UpdateFetchTask extends AsyncTask{
    /** @var string */
    private $build;
    /** @var bool */
    private $install;

    /**
     * @param string $build
     * @param bool $install
     */
    public function __construct(string $build, bool $install){
        $this->build = $build;
        $this->install = $install;
    }

    public function onRun(){
        switch($this->build){
            case "beta":
                $url = "https://api.github.com/repos/WeekThor/EssentialsPE/releases"; // Github repository for 'Beta' releases
                break;
        }
        $i = json_decode(Utils::getURL($url), true);

        $r = [];
        switch(strtolower($this->build)){
            case "beta":
                $i = $i[0]; // Grab the latest version from Github releases... Doesn't matter if it's Beta or Stable :3
                $r["version"] = substr($i["name"], 13);
                $r["downloadURL"] = $i["assets"][0]["browser_download_url"];
                break;
        }
        $this->setResult($r);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server){
        /** @var Loader $ess */
        $ess = $server->getPluginManager()->getPlugin("EssentialsPE");

        // Tricky move for better "version" comparison...
        $currentVersion = $this->correctVersion($ess->getDescription()->getVersion());
        $v = $this->getResult()["version"];

        if($currentVersion < $v or $this->build === "development"){
            $continue = true;
            $message = TextFormat::AQUA . "[EssentialsPE]" . TextFormat::GREEN .
                ($this->build === "development" ?
                    "Fetching latest EssentialsPE development build..." :
                    " Новая " . TextFormat::YELLOW . $this->build . TextFormat::GREEN . " Версия найдена!!"
                ) .
                " Версия: " . TextFormat::YELLOW . $v . TextFormat::GREEN .
                ($this->install !== true ? "" : ", " . TextFormat::LIGHT_PURPLE . "Установка...\n&4Помните, что у новой версии может не быть перевода");
        }else{
            $continue = false;
            $message = TextFormat::AQUA . "[EssentialsPE]" . TextFormat::YELLOW . " Нет новых релизов, у вас последняя версия(:";
        }
        $ess->getAPI()->broadcastUpdateAvailability($message);
        if($continue && $this->install){
            $server->getScheduler()->scheduleAsyncTask($task = new UpdateInstallTask($ess->getAPI(), $this->getResult()["downloadURL"], $server->getPluginPath(), $v));
            $ess->getAPI()->updaterDownloadTask = $task;
        }
    }

    /**
     * @param string $version
     * @return string
     */
    protected function correctVersion(string $version){
        if(($beta = stripos($version, "Beta")) !== false){
            str_replace("Beta", ".", $version);
        }
        $version = explode(".", preg_replace("/[^0-9\.]+/", "", $version));
        $beta = 0;
        if(count($version) > 3){
            $beta = array_pop($version);
            $beta = (count($beta) < 2 ? 0 : "") . $beta;
        }
        return implode("", $version) . "." . $beta;
    }
}
