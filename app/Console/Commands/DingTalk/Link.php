<?php

namespace App\Console\Commands\DingTalk;

class Link
{
    const TYPE = "link";

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
            "link" => [
                "title" => $this->title,
                "text" => $this->text,
                "picUrl" => $this->picUrl,
                "messageUrl" => $this->messageUrl,
            ],
            "at" => [
                "atMobiles" => (array)$this->atMobiles,
                "isAtAll" => $this->isAtAll,
            ]
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}
