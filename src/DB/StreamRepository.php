<?php

declare(strict_types=1);

namespace App\DB;

use DateTimeImmutable;
use PDO;

class StreamRepository
{
    public function __construct(
        private PDO $pdo
    ) {

    }

    public function getAllStreams()
    {
        $query = $this->pdo->prepare(<<<SQL
    SELECT * FROM stream
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
            $channel = new Stream(
                $row['channel_id'],
                $row['service_stream_id'],
                $row['title'],
                $this->stringToDate($row['started_at']),
                $this->stringToDate($row['stopped_at'])
            );

            $channel->setId($row['stream_id']);
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