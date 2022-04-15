<?php

declare(strict_types=1);

namespace App\DB;

use DateTimeImmutable;
use DateTimeInterface;

class Stream
{
    private ?int $id;
    public int $channelId;
    public int $serviceStreamId;
    public string $title;
    public DateTimeImmutable $startedAt;
    public ?DateTimeImmutable $stoppedAt;

    public function __construct(
        int $channelId,
        int $serviceStreamId,
        string $title,
        DateTimeInterface $startedAt,
        ?DateTimeInterface $stoppedAt = null
    ) {
        $this->id = null;
        $this->channelId = $channelId;
        $this->serviceStreamId = $serviceStreamId;
        $this->title = $title;
        $this->startedAt = DateTimeImmutable::createFromInterface($startedAt);

        if ($stoppedAt instanceof DateTimeInterface) {
            $this->stoppedAt = DateTimeImmutable::createFromInterface($stoppedAt);
        } else {
            $this->stoppedAt = null;
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
}