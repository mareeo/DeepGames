<?php

declare(strict_types=1);

namespace App\DB;

use DateTimeImmutable;
use PDO;

class ChannelRepository
{
    public function __construct(
        private PDO $pdo
    ) {

    }

    /**
     * @return Channel[]
     */
    public function getAllChannels(): array
    {
        $query = $this->pdo->prepare('SELECT * FROM channel');

        $query->execute();

        $results = $query->fetchAll();

        if (is_array($results)) {
            return $this->map($results);
        } else {
            return [];
        }
    }

    /**
     * @return Channel[]
     */
    public function getChannelsForService(string $service): array
    {
        $query = $this->pdo->prepare('SELECT * FROM channel WHERE service = ?');

        $query->execute([$service]);

        $results = $query->fetchAll();

        if (is_array($results)) {
            return $this->map($results);
        } else {
            return [];
        }
    }

    public function saveChannel(Channel $channel): void
    {
        if ($channel->getId() === null) {
            $this->createChannel($channel);
        } else {
            $this->updateChannel($channel);
        }
    }

    private function updateChannel(Channel $channel)
    {
        $query = $this->pdo->prepare(<<<SQL
            UPDATE channel
            SET
                last_updated = :lastUpdated,
                title = :title,
                subtitle = :subtitle,
                image = :image,
                live = :live,
                viewers = :viewers,
                last_stream = :lastStream
            WHERE
                channel_id = :id
        SQL);

        $params = [
            ':id' => $channel->getId(),
            ':lastUpdated' => $channel->lastUpdated?->format('Y-m-d H:i:s'),
            ':title' => $channel->title,
            ':subtitle'=> $channel->subtitle,
            ':image' => $channel->image,
            ':live' => (int)$channel->live,
            ':viewers' => $channel->viewers,
            ':lastStream' => $channel->lastStream?->format('Y-m-d H:i:s')
        ];

        $query->execute($params);
    }

    private function createChannel(Channel $channel)
    {
        $query = $this->pdo->prepare(<<<SQL
            INSERT INTO channel (name, service, last_updated, title, subtitle, image, live, viewers, last_stream)
            VALUES (:name, :service, :lastUpdated, :title, :subtitle, :image, :live, :viewers, :lastStream)
        SQL);

        $params = [
            ':name' => $channel->name,
            ':service' => $channel->service,
            ':lastUpdated' => $channel->lastUpdated?->format('Y-m-d H:i:s'),
            ':title' => $channel->title,
            ':subtitle'=> $channel->subtitle,
            ':image' => $channel->image,
            ':live' => (int)$channel->live,
            ':viewers' => $channel->viewers,
            ':lastStream' => $channel->lastStream?->format('Y-m-d H:i:s')
        ];

        $query->execute($params);

        $id = (int)$this->pdo->lastInsertId();

        $channel->setId($id);
    }

    /**
     * @param array $results
     * @return Channel[]
     */
    private function map(array $results): array
    {
        $o = [];
        foreach ($results as $row) {
            $channel = new Channel(
                $row['name'],
                $row['service'],
                $this->stringToDate($row['last_updated']),
                $row['title'],
                $row['subtitle'],
                $row['image'],
                (bool)$row['live'],
                $row['viewers'],
                $this->stringToDate($row['last_stream'])
            );

            $channel->setId($row['channel_id']);
            $o[] = $channel;
        }

        return $o;
    }

    private function stringToDate(?string $string): ?DateTimeImmutable
    {
            if ($string === null) {
                return null;
            }

            $datetime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $string);

            if (!$datetime instanceof DateTimeImmutable) {
                return null;
            } else {
                return $datetime;
            }


    }
}