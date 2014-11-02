<?php

use DeepGames\HitboxInterface;
use DeepGames\TwitchInterface;
use DeepGames\LivestreamInterface;
/**
 * Created by PhpStorm.
 * User: Dustin
 * Date: 10/25/2014
 * Time: 2:05 PM
 */


require('vendor/autoload.php');
require('config.php');

if(php_sapi_name() !== 'cli') {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$pdo = new PDO(DB_TYPE .":host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS)
or die("No database connection");

$HbInterface = new HitboxInterface($pdo);
$HbInterface->updateTeamData();

$twitchInterface = new TwitchInterface($pdo);
$twitchInterface->getChannelData("deepgamers");

$livestreamInterface = new LivestreamInterface($pdo);
$livestreamInterface->getChannelData("deepgamers");

