<?php


namespace DeepGamers\Application\Actions;


use DI\Container;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDO;

class ApiChannelsAction
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $channels = $this->getChannels();
        $response->getBody()->write(json_encode($channels));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getChannels(): array
    {
        $dbh = $this->container->get(PDO::class);

        $query = $dbh->prepare(<<<SQL
    SELECT * FROM stream
SQL
        );

        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

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