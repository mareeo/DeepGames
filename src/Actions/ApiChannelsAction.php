<?php


namespace App\Actions;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDO;

class ApiChannelsAction
{
    /** @var PDO */
    private $dbh;

    public function __construct(PDO $dbh)
    {
        $this->dbh = $dbh;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $channels = $this->getChannels();
        $response->getBody()->write(json_encode($channels));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getChannels(): array
    {
        $query = $this->dbh->prepare(<<<SQL
    SELECT * FROM stream
SQL
        );

        $query->execute();

        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $output = [
            "live" => [],
            "notLive" => []
        ];

        foreach($results as $result) {
            if($result['live']) {
                $output['live'][] = $result;
            } else {
                $output['notLive'][] = $result;
            }
        }


        // Sort the live channels by viewer count
        usort($output['live'], function ($a, $b) {
            if($a['viewers'] == $b['viewers'])
                return 0;

            if($a['viewers'] < $b['viewers'])
                return 1;
            return -1;
        });

        return $output;
    }
}