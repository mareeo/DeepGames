<?php
/**
 * Created by PhpStorm.
 * User: Dustin
 * Date: 10/25/2014
 * Time: 3:17 PM
 */

namespace DeepGames;

use DB\SQL;
use \PDO;
use \GuzzleHttp\Client;


class HitboxInterface {

    /**
     *@var \PDO Database Handle
     */
    private $dbh;

    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzle;

    const PREFIX = "http://edge.sf.hitbox.tv";

    public function __construct(PDO $dbh) {
        $this->dbh = $dbh;
        $this->guzzle = new Client();
    }

    public function updateTeamData() {
        $team = $this->getTeamMembers();

        $this->removeOldChannels($team);

        foreach($team as $member) {
            $this->getChannelData($member);
        }
    }

    /**
     * @return string[]
     */
    private function getTeamMembers()
    {
        $client = new Client();

        /**
         * @var \GuzzleHttp\Message\Response $response
         */
        $response = $this->guzzle->get('http://api.hitbox.tv//teams/deepgames');

        $team = $response->json();

        $members = $team['teams'][0]['members'];

        $team = [];

        foreach ($members as $member) {
            $team[] = strtolower($member['user_name']);
        }

        return $team;

    }

    private function getChannelData($channel) {

        $url = "http://api.hitbox.tv/media/live/$channel";

        // Asynchronous call

        /**
         * @var \GuzzleHttp\Message\Response $response
         */
        $response = $this->guzzle->get($url, ['future' => true]);

        // Call the function when the response completes
        $response->then(
            function ($response) use ($channel) {
                $rawData = $response->json(['object' => true]);
                $data = $this->translate($channel, $rawData);
                $this->updateCache($channel, $data);
            },
            function($error) {
                error_log($error->getMessage());
            });

    }

    private function updateCache($channel, $data) {

        $query = $this->dbh->prepare(<<<SQL
        START TRANSACTION;
        DELETE FROM channel WHERE service = 'hitbox' AND channel = :channel;
        INSERT INTO channel (channel, service, live,  data)
                    VALUES (:channel, 'hitbox', :isLive, :data);
        COMMIT;
SQL
);

        $query->bindValue(':channel', $channel);
        $query->bindValue(':isLive', $data['live']);
        $query->bindValue(':data', json_encode($data));

        return $query->execute();

    }

    private function removeOldChannels(Array $team) {

        $teamString = implode(', ', $team);

        $query = $this->dbh->prepare(<<<SQL
        DELETE FROM channel
        WHERE service = 'hitbox'
        AND channel NOT IN (:channels);
SQL
);
        $query->bindValue(':channels', $teamString);
        $query->execute();
    }

    private function translate($channel, \stdClass $data) {

        $output = array(
            'name'         => $data->livestream[0]->channel->user_name,
            'title'        => $data->livestream[0]->media_status,
            'logo'         => self::PREFIX . $data->livestream[0]->channel->user_logo,
            'thumbnail'    => self::PREFIX . $data->livestream[0]->media_thumbnail,
            'viewers'      => $data->livestream[0]->media_views,
            'game'         => $data->livestream[0]->category_name,
            'live'         => (bool)$data->livestream[0]->media_is_live,
            'player_code'  => '<iframe width="100%" height="100%" src="http://hitbox.tv/#!/embed/' . $channel . '?autoplay=true" frameborder="0" allowfullscreen></iframe>',
            'chat_code'    => '<iframe width="100%" height="100%" src="http://www.hitbox.tv/embedchat/' . $channel . '?autoconnect=true" frameborder="0" allowfullscreen></iframe>',
            'service'      => 'hitbox'
        );

        return $output;
    }

}
