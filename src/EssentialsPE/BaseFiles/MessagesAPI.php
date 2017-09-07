<?php
namespace EssentialsPE\BaseFiles;


use EssentialsPE\Loader;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class MessagesAPI {
    const VERSION = "2.0.0";
    
    /** @var Loader */
    private $loader;
    /** @var MessageStore */
    private $store;
    /** @var MessageStore */
    private $originalStore;
    
    /**
     * @param Loader $loader
     */
    public function __construct(Loader $loader){
        $this->loader = $loader;
        $loader->saveResource("Messages.yml");
        $messagesConfig = new Config($loader->getDataFolder() . "Messages.yml", Config::YAML);
        if($messagesConfig->get("version") != self::VERSION){
            $loader->getLogger()->debug(TextFormat::RED . "An invalid language file was found, generating a new one...");
            $loader->saveResource("Messages.yml", true);
        }
        $language = $messagesConfig->get("language");
        $messages = [];
        foreach($messagesConfig->get("messages")[$language] as $message) {
            $messages[] = self::translateColors($message);
        }
        $this->store = new MessageStore($language, $messages);
        fwrite(fopen($loader->getDataFolder() . "MessagesOriginal.yml", "w"), $loader->getResource("Messages.yml"));
        $originalConfig = new Config($loader->getDataFolder() . "MessagesOriginal.yml", Config::YAML);
        $originalLanguage = $originalConfig->get("language");
        $language = (isset($originalConfig->get("messages")[$language])) ? $language : $originalLanguage;
        $messages = [];
        foreach($originalConfig->get("messages")[$language] as $message){
            $messages[] = $message;
        }
        $this->originalStore = new MessageStore($language, $messages);
    }
    
    /**
     * @return Loader
     */
    public function getLoader(): Loader{
        return $this->loader;
    }
    
    /**
     * @return MessageStore
     */
    public function getStore(): MessageStore{
        return $this->store;
    }
    
    /**
     * @return MessageStore
     */
    public function getOriginalStore(): MessageStore{
        return $this->originalStore;
    }
    
    /**
     * @param string $identifier
     * @param array $args
     * @return string
     */
    public function getMessage(string $identifier, $args = []): string{
        if(($msg = $this->store->getMessage($identifier, $args)) != null){
            return $msg;
        }else{
            return $this->originalStore->getMessage($identifier, $args);
        }
    }
    
    /**
     * @param string $message
     * @return string
     */
    public static function translateColors($message): string{
        $message = str_replace("{BLACK}", TextFormat::BLACK, $message);
        $message = str_replace("{DARK_BLUE}", TextFormat::DARK_BLUE, $message);
        $message = str_replace("{DARK_GREEN}", TextFormat::DARK_GREEN, $message);
        $message = str_replace("{DARK_AQUA}", TextFormat::DARK_AQUA, $message);
        $message = str_replace("{DARK_RED}", TextFormat::DARK_RED, $message);
        $message = str_replace("{DARK_PURPLE}", TextFormat::DARK_PURPLE, $message);
        $message = str_replace("{ORANGE}", TextFormat::GOLD, $message);
        $message = str_replace("{GRAY}", TextFormat::GRAY, $message);
        $message = str_replace("{DARK_GRAY}", TextFormat::DARK_GRAY, $message);
        $message = str_replace("{BLUE}", TextFormat::BLUE, $message);
        $message = str_replace("{GREEN}", TextFormat::GREEN, $message);
        $message = str_replace("{AQUA}", TextFormat::AQUA, $message);
        $message = str_replace("{RED}", TextFormat::RED, $message);
        $message = str_replace("{LIGHT_PURPLE}", TextFormat::LIGHT_PURPLE, $message);
        $message = str_replace("{YELLOW}", TextFormat::YELLOW, $message);
        $message = str_replace("{WHITE}", TextFormat::WHITE, $message);
        $message = str_replace("{OBFUSCATED}", TextFormat::OBFUSCATED, $message);
        $message = str_replace("{BOLD}", TextFormat::BOLD, $message);
        $message = str_replace("{STRIKETHROUGH}", TextFormat::STRIKETHROUGH, $message);
        $message = str_replace("{UNDERLINE}", TextFormat::UNDERLINE, $message);
        $message = str_replace("{ITALIC}", TextFormat::ITALIC, $message);
        $message = str_replace("{RESET}", TextFormat::RESET, $message);
        return $message;
    }
}