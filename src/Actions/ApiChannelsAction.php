<?php


namespace App\Actions;

use App\DB\Channel;
use App\DB\ChannelRepository;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDO;

class ApiChannelsAction
{
    /** @var PDO */
    private $dbh;

    private ChannelRepository $channelRepo;

    public function __construct(PDO $dbh, ChannelRepository $channelRepo)
    {
        $this->dbh = $dbh;
        $this->channelRepo = $channelRepo;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $channels = $this->getChannels();
        $response->getBody()->write(json_encode($channels));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getChannels(): array
    {
        $results = $this->channelRepo->getAllChannels();

        $output = [
            "live" => [],
            "notLive" => []
        ];

        foreach($results as $result) {
            if($result->live) {
                $output['live'][] = $result;
            } else {
                $output['notLive'][] = $result;
            }
        }


        // Sort the live channels by viewer count
        usort($output['live'], function (Channel $a, Channel $b) {
            if($a->viewers == $b->viewers)
                return 0;

            if($a->viewers < $b->viewers)
                return 1;
            return -1;
        });

        return $output;
    }
}