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

    /**
     * Undocumented function
     *
     * @return Stream[]
     */
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
     * Undocumented function
     *
     * @return Stream[]
     */
    public function getCurrentStreams(): array
    {
        $query = $this->pdo->prepare('SELECT * FROM stream WHERE stopped_at IS NULL');

        $query->execute();

        $results = $query->fetchAll();

        if (is_array($results)) {
            return $this->map($results);
        } else {
            return [];
        }
    }

    /**
     * Undocumented function
     *
     * @param integer $channelId
     * @return Stream[]
     */
    public function getStreamsForChannel(int $channelId): array
    {
        $query = $this->pdo->prepare('SELECT * FROM stream WHERE channel_id = ?');

        $query->execute([$channelId]);

        $results = $query->fetchAll();

        if (is_array($results)) {
            return $this->map($results);
        } else {
            return [];
        }
    }

    public function save(Stream $stream): void
    {
        if ($stream->getId() === null) {
            $this->insert($stream);
        } else {
            $this->update($stream);
        }
    }

    public function insert(Stream $stream)
    {
        $query = $this->pdo->prepare(<<<SQL
            INSERT INTO stream (channel_id, service_stream_id, title, started_at, stopped_at)
            VALUES (?,?,?,?,?)
        SQL);

        $params = [
            $stream->channelId,
            $stream->serviceStreamId,
            $stream->title,
            $stream->startedAt->format('Y-m-d H:i:s'),
            $stream->stoppedAt?->format('Y-m-d H:i:s')
        ];

        $query->execute($params);

        $id = (int)$this->pdo->lastInsertId();

        $stream->setId($id);
    }

    public function update(Stream $stream)
    {
        $query = $this->pdo->prepare(<<<SQL
            UPDATE stream
            SET
                channel_id = ?,
                service_stream_id = ?,
                title = ?,
                started_at = ?,
                stopped_at = ?
            WHERE
                stream_id = ?
        SQL);

        $params = [
            $stream->channelId,
            $stream->serviceStreamId,
            $stream->title,
            $stream->startedAt->format('Y-m-d H:i:s'),
            $stream->stoppedAt?->format('Y-m-d H:i:s'),
            $stream->getId()
        ];

        $query->execute($params);
    }

    public function delete(Stream $stream)
    {
        $query = $this->pdo->prepare(<<<SQL
            DELETE FROM channel WHERE channel_id = ?
        SQL);

        $query->execute([$stream->getId()]);
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