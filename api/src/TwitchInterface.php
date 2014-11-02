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


class TwitchInterface {

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

        $url = "https://api.twitch.tv/kraken/streams/$channel";

        /**
         * @var \GuzzleHttp\Message\Response $response
         */
        try {
            $response = $this->guzzle->get($url, ['verify' => false]);

            $liveJSON = $response->json(['object' => true]);


            // If the channel is live
            if ($liveJSON->stream != null) {

                // If game is specified, set it to an empty string
                if($liveJSON->stream->channel->game === null)
                    $liveJSON->stream->channel->game = '';

                // Create the output array
                $output = [
                    'name'         => $liveJSON->stream->channel->display_name,
                    'title'        => $liveJSON->stream->channel->status,
                    'logo'         => $liveJSON->stream->channel->logo,
                    'thumbnail'    => $liveJSON->stream->preview->medium,
                    'viewers'      => $liveJSON->stream->viewers,
                    'game'         => $liveJSON->stream->channel->game,
                    'live'         => true,
                    'player_code'  => $this->getPlayerCode($channel),
                    'chat_code'    => $this->getChatCode($channel),
                    'service'      => 'twitch'
                ];

                // If the channel isn't live
            } else {

                // We have to get the channel info from a different file
                $offlineURL = $liveJSON->_links->channel;
                $response = $this->guzzle->get($offlineURL, ['verify' => false]);
                $offlineJSON = $response->json(['object' => true]);

                // If game is specified, set it to an empty string
                if ($offlineJSON->game === null)
                    $offlineJSON->game = '';

                // Create the output array
                $output = [
                    'name' => $offlineJSON->display_name,
                    'title' => $offlineJSON->status,
                    'logo' => $offlineJSON->logo,
                    'thumbnail' => $offlineJSON->logo,
                    'viewers' => 0,
                    'game' => $offlineJSON->game,
                    'live' => false,
                    'player_code' => $this->getPlayerCode($channel),
                    'chat_code' => $this->getChatCode($channel),
                    'service' => 'twitch'
                ];
            }

        } catch (\Exception $e) {
            error_log("Unable to update Twitch channel $channel: " . $e->getMessage());
            return null;
        }

        $this->updateCache($channel, $output);

        return $output;
    }

    private function updateCache($username, $data) {

        $query = $this->dbh->prepare(<<<SQL
        START TRANSACTION;
        DELETE FROM channel WHERE channel = :channel AND service = :service;
        INSERT INTO channel (channel, service, live, data) VALUES (:channel, :service, :isLive,  :data);
        COMMIT;
SQL
);

        $query->bindValue(':channel', $username);
        $query->bindValue(':service', "twitch");
        $query->bindValue(':isLive', $data['live']);
        $query->bindValue(':data', json_encode($data));

        return $query->execute();

    }

    private function getPlayerCode($channel) {

        $playerCode = '
<object type="application/x-shockwave-flash" height="100%" width="100%" data="http://www.twitch.tv/widgets/live_embed_player.swf?channel='.$channel.'">
    <param name="allowFullScreen" value="true" />
    <param name="allowScriptAccess" value="always" />
    <param name="allowNetworking" value="all" />
    <param name="wmode" value="gpu"/>
    <param name="movie" value="http://www.twitch.tv/widgets/live_embed_player.swf" />
    <param name="flashvars" value="hostname=www.twitch.tv&channel='.$channel.'&auto_play=true&start_volume=100" />
</object>
';

        return $playerCode;

    }

    private function getChatCode($channel) {
        $chatCode = '<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="http://twitch.tv/'.$channel.'/chat"></iframe>';
        return $chatCode;
    }




}
