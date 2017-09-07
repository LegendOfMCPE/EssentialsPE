<?php
namespace EssentialsPE\BaseFiles;

class MessageStore {
    /** @var string */
    private $languageIdentifier;
    /** @var array */
    private $messages = [];
    
    /**
     * @param string $languageIdentifier
     * @param array $messages
     */
    public function __construct(string $languageIdentifier, array $messages){
        $this->languageIdentifier = $languageIdentifier;
        $this->messages = $messages;
    }
    
    /**
     * @return string
     */
    public function getLanguageIdentifier(): string{
        return $this->languageIdentifier;
    }
    
    /**
     * @return array
     */
    public function getMessages(): array{
        return $this->messages;
    }
    
    /**
     * @param string $identifier
     * @param array $args
     * @return string|null
     */
    public function getMessage(string $identifier, $args = []){
        if(array_key_exists($identifier, $this->messages)){
            $message = $this->messages[$identifier];
            foreach($args as $arg => $value){
                $message = str_replace("{" . $arg . "}", $value, $message);
            }
            return $message;
        }else{
            return null;
        }
    }
}