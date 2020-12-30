<?php

namespace App\DB;

use DateTime;

class Stream
{
    private int $id;
    public string $name;
    public string $service;
    public ?DateTime $lastUpdated;
    public ?DateTime $lastLive;
    public ?string $title;
    public ?string $game;
    public ?string $thumbnail;
    public bool $live;
    public int $viewers;

    public ?int $twitchGameId;

    public function __construct(string $name, string $service, bool $live, string $thumbnail, string $title, int $viewers)
    {
        $this->name = $name;
        $this->service = $service;
        $this->live = $live;
        $this->thumbnail = $thumbnail;
        $this->title = $title;
        $this->viewers = $viewers;
        $this->game = null;
        $this->twitchGameId = null;
    }


}