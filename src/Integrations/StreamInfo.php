<?php

namespace DeepGamers\Integrations;


class StreamInfo
{
    /** @var string */
    public $name;

    /** @var string */
    public $service;

    /** @var string */
    public $title;

    /** @var string */
    public $thumbnail;

    /** @var bool */
    public $live;

    /** @var int */
    public $viewers;

    public function __construct(string $name, string $service, bool $live, string $thumbnail, string $title, int $viewers)
    {
        $this->name = $name;
        $this->service = $service;
        $this->live = $live;
        $this->thumbnail = $thumbnail;
        $this->title = $title;
        $this->viewers = $viewers;
    }
}