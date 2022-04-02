<?php

declare(strict_types=1);

namespace App\DB;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

class Channel implements JsonSerializable
{
    private ?int $id;
    public string $name;
    public string $service;
    public ?DateTimeImmutable $lastUpdated;
    public ?string $title;
    public ?string $subtitle;
    public ?string $image;
    public bool $live;
    public int $viewers;
    public ?DateTimeImmutable $lastStream;

    public function __construct(
        string $name,
        string $service,
        ?DateTimeInterface $lastUpdated = null,
        ?string $title = null,
        ?string $subtitle = null,
        ?string $image = null,
        bool $live = false,
        int $viewers = 0,
        ?DateTimeInterface $lastStream = null
    ) {
        $this->id = null;
        $this->name = $name;
        $this->service = $service;

        if ($lastUpdated instanceof DateTimeInterface) {
            $this->lastUpdated = DateTimeImmutable::createFromInterface($lastUpdated);
        } else {
            $this->lastUpdated = null;
        }
        
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->image = $image;
        $this->live = $live;
        $this->viewers = $viewers;

        if ($lastStream instanceof DateTimeInterface) {
            $this->lastStream = DateTimeImmutable::createFromInterface($lastStream);
        } else {
            $this->lastStream = null;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id === null) {
            $this->id = $id;
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'service' => $this->service,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => $this->image,
            'live' => $this->live,
            'viewers' => $this->viewers
        ];
    }
}