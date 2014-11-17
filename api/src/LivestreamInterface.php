<?php
/**
 * Created by PhpStorm.
 * User: Dustin
 * Date: 10/25/2014
 * Time: 3:17 PM
 */

namespace DeepGames;

use \PDO;
use \GuzzleHttp\Client;


class LivestreamInterface {

    /**
     *@var \PDO Database Handle
     */
    private $dbh;

    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzle;

    public function __construct(PDO $dbh) {
        $this->dbh = $dbh;
        $this->guzzle = new Client();
    }

    public function getChannelData($channel) {

        $url = 'http://x'.$channel.'x.api.channel.livestream.com/2.0/info.json';

        /**
         * @var \GuzzleHttp\Message\Response $response
         */
        try {
            $response = $this->guzzle->get($url);

            $data = $response->json(['object' => true]);

            $output = [
                'name'         => $channel,
                'title'        => $data->channel->title,
                'logo'         => $data->channel->image->url,
                'thumbnail'    => "http://thumbnail.api.livestream.com/thumbnail?name=$channel&t=".time(),
                'viewers'      => (int) $data->channel->currentViewerCount,
                'game'         => "",
                'live'         => $data->channel->isLive,
                'player_code'  => $this->getPlayerCode($channel),
                'chat_code'    => $this->getChatCode($channel),
                'service'      => 'livestream'
            ];


        } catch (\Exception $e) {
            error_log("Unable to update Livestream channel $channel: " . $e->getMessage());
            return null;
        }

        $this->updateCache($channel, $output);

        return $output;
    }

    private function updateCache($channel, $data) {

        $query = $this->dbh->prepare(<<<SQL
        START TRANSACTION;
        DELETE FROM channel WHERE channel = :channel AND service = :service;
        INSERT INTO channel (channel, service, live, data) VALUES (:channel, :service, :isLive,  :data);
        COMMIT;
SQL
);

        $query->bindValue(':channel', $channel);
        $query->bindValue(':service', "livestream");
        $query->bindValue(':isLive', $data['live']);
        $query->bindValue(':data', json_encode($data));

        return $query->execute();

    }

    private function getPlayerCode($channel) {

        $playerCode = '<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="http://localhost/newdeep/lsPlayer.php?channel='.$channel.'"></iframe>';

        return $playerCode;

    }

    private function getChatCode($channel) {
        if($channel == 'deepgamers') {
            $chatCode = '<a href=http://www.deepgamers.com/images/socialcat.jpg target=_blank><div id="chatoverlap"></div></a><embed type="application/x-shockwave-flash" src="http://www.livestream.com/procaster/swfs/Procaster.swf?channel='.$channel.'" width="100%" height="100%" style="undefined" id="Procaster" name="Procaster" quality="high" allowscriptaccess="always" wmode="transparent" bgcolor="#000000"></embed>';
        } else {
            $chatCode = '<embed type="application/x-shockwave-flash" src="http://cdn.livestream.com/chat/LivestreamChat.swf?&channel='.$channel.'" width="100%" height="100%"  bgcolor="#ffffff"></embed>';
        }

        return $chatCode;
    }




}
