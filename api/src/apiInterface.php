<?php

namespace DeepGames;

/**
 * This class is used to interface with various services APIs.
 *
 * It queries the service's API, puts the data in the format we want,
 * and then returns the data.
 * 
 * Currently supported services are: "livestream", "hitbox", and "twitch"
 *
 */
class apiInterface {
    
    /**
     * Use this function to get channel information
     *
     * @param string $channel The channel to fetch
     * @param string $service The service to query
     * @return mixed[] An array containing information about the channel
     */
    public static function getChannel($channel, $service) {
        
        if($service == 'hitbox')
            return self::getHitbox($channel);
        elseif($service == 'twitch')
            return self::getTwitch($channel);
        elseif($service == 'livestream')
            return self::getLivestream($channel);
        else
            return array ("error" => "Unsupported Service");
        
    }

    /**
     * Function to get information from an external URL using curl
     *
     * @param string $url The URL to query
     */
    private static function get_url_contents($url){
        
        $crl = curl_init();
        
        curl_setopt ($crl, CURLOPT_URL,$url);
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, 3);
        
        $ret = curl_exec($crl);
        curl_close($crl);
        
        return $ret;
    }
    
    /**
     * Private method used to query the Hitbox.tv API
     */
    private static function getHitbox($channel) {
        $url = "http://api.hitbox.tv/media/live/$channel";
        
        $prefix = "http://edge.sf.hitbox.tv";
        $response = self::get_url_contents($url);
        
        if($response == 'no_media_found') {
            return array ("error" => "Channel not found");
        }
        
        $data = json_decode($response);
        
        // If game is specified, set it to an empty string
        if($data->livestream[0]->category_name === null)
            $data->livestream[0]->category_name = '';

        $output = array(
            'name'         => $data->livestream[0]->channel->user_name,
            'title'        => $data->livestream[0]->media_status,
            'logo'         => $prefix . $data->livestream[0]->channel->user_logo,
            'thumbnail'    => $prefix . $data->livestream[0]->media_thumbnail,
            'viewers'      => $data->livestream[0]->media_views,
            'game'         => $data->livestream[0]->category_name,
            'live'         => (bool)$data->livestream[0]->media_is_live,
            'player_code'  => '<iframe width="100%" height="100%" src="http://hitbox.tv/#!/embed/' . $channel . '?autoplay=true" frameborder="0" allowfullscreen></iframe>',
            'chat_code'    => '<iframe width="100%" height="100%" src="http://www.hitbox.tv/embedchat/' . $channel . '" frameborder="0" allowfullscreen></iframe>',
            'service'      => 'hitbox'
        );
        
        return $output;
    }
    
    /**
     * Private method used to query the Twitch.tv API
     */
    private static function getTwitch($channel) {
   
   $liveURL="https://api.twitch.tv/kraken/streams/$channel";
   $liveJSON  = json_decode(self::get_url_contents($liveURL));
      
   // If the channel doesn't exist
        if(isset($liveJSON->error) && $liveJSON->error == 'Not Found') {
            return array ("error" => "Channel not found");
        }
        
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
         
        $chatCode = '<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="http://twitch.tv/'.$channel.'/chat"></iframe>';
      
   
   // If the channel is live
   if ($liveJSON->stream != null) {
            
            // If game is specified, set it to an empty string
            if($liveJSON->stream->channel->game === null) 
                $liveJSON->stream->channel->game = '';
            
       // Create the output array
       $output = array(
        'name'         => $liveJSON->stream->channel->display_name,
        'title'        => $liveJSON->stream->channel->status,
        'logo'         => $liveJSON->stream->channel->logo,
        'thumbnail'    => $liveJSON->stream->preview->medium,
        'viewers'      => $liveJSON->stream->viewers,
        'game'         => $liveJSON->stream->channel->game,
        'live'         => true,
        'player_code'  => $playerCode,
        'chat_code'    => $chatCode,
        'service'      => 'twitch'
    );
   
   // If the channel isn't live
   } else {
      
       // We have to get the channel info from a different file
       $offlineURL = $liveJSON->_links->channel;
       $offlineJSON = json_decode(self::get_url_contents($offlineURL));
            
            // If game is specified, set it to an empty string
            if($offlineJSON->game === null) 
                $offlineJSON->game = '';
            
            // Create the output array
            $output = array(
                'name'         => $offlineJSON->display_name,
                'title'        => $offlineJSON->status,
                'logo'         => $offlineJSON->logo,
      'thumbnail'    => $offlineJSON->logo,
      'viewers'      => 0,
      'game'         => $offlineJSON->game,
      'live'         => false,
      'player_code'  => $playerCode,
      'chat_code'    => $chatCode,
      'service'      => 'twitch'
            );
   }
        
   return $output;
    }
    /**
     * Private method used to query the Livestream.com API
     */
    private static function getLivestream($channel) {
        
        // Get data from API
        $url = 'http://x'.$channel.'x.api.channel.livestream.com/2.0/info.json';
        $data =  json_decode(self::get_url_contents($url));
        
        // Make sure the channel exists
   if(count($data) != 1) {
       //if(preg_match("/^<h1>404 Not Found<\/h1>$/", $data) == 1)   
       return array ("error" => "Channel not found");
   }
        
        
        if($channel == 'deepgamers') {
            $chatcode = '<a href=http://www.deepgamers.com/images/socialcat.jpg target=_blank><div id="chatoverlap"></div></a><embed type="application/x-shockwave-flash" src="http://www.livestream.com/procaster/swfs/Procaster.swf?channel='.$channel.'" width="100%" height="100%" style="undefined" id="Procaster" name="Procaster" quality="high" allowscriptaccess="always" wmode="transparent" bgcolor="#000000"></embed>';;
        } else {
            $chatcode = '<embed type="application/x-shockwave-flash" src="http://cdn.livestream.com/chat/LivestreamChat.swf?&channel='.$channel.'" width="100%" height="100%"  bgcolor="#ffffff"></embed>';
        }
        
 
        $output = array(
            'name'         => $channel,
            'title'        => $data->channel->title,
            'logo'         => $data->channel->image->url,
            'thumbnail'    => "http://thumbnail.api.livestream.com/thumbnail?name=$channel&t=".time(),
            'viewers'      => $data->channel->currentViewerCount,
            'game'         => "",
            'live'         => $data->channel->isLive,
            'player_code'  => '<iframe frameborder="0" scrolling="no" width="100%" height="100%" src="http://new2.deepgamers.com/lsPlayer.php?channel='.$channel.'"></iframe>',
            'chat_code'    => $chatcode,
            'service'      => 'livestream'
        );
        
        return $output;
        
    }
    
   /**
    * Gives you all members of a hitbox team
    */
   public static function getHitboxTeam() {
      $url = 'http://api.hitbox.tv/teams/deepgames';
      $json = json_decode(self::get_url_contents($url));
      
      $team = $json->teams[0];
      $info = $team->info;
      $founder = $team->founder;
      $members = $team->members;
      
      
      foreach($members as &$member) {
         $output[] = strtolower($member->user_name);
      }
      
      sort($output);
      
      return $output;
   }
}
