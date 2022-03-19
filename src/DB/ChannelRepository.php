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

    public function getAllChannels()
    {
        $query = $this->pdo->prepare(<<<SQL
    SELECT * FROM channel
SQL
        );

        $query->execute();

        $results = $query->fetchAll();

        if (is_array($results)) {
            return $this->map($results);
        } else {
            return [];
        }
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