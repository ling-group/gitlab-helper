<?php

namespace App\Console\Commands\DingTalk;

class Markdown
{
    const TYPE = "markdown";

    public $title = "";
    public $text = "";
    public $picUrl = "";
    public $messageUrl = "";
    public $atMobiles = "";

    public $isAtAll = false;

    public function __toString()
    {
        return json_encode([
            "msgtype" => self::TYPE,
            strtolower(class_basename(__CLASS__)) => [
                "title" => $this->title,
                "text" => $this->title. ' '.$this->text . sprintf(" [点击这里](%s)", $this->messageUrl),
            ],
            "at" => [
                "atMobiles" => (array)$this->atMobiles,
                "isAtAll" => $this->isAtAll,
            ]
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}
