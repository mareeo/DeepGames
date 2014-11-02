<?php

require('vendor/autoload.php');
require('config.php');


$f3 = require('vendor/bcosca/fatfree/lib/base.php');

/**
 * Root route.  Return API endpoints.
 */
$f3->route('GET /',
    function() {

        header('Content-Type: application/json');
        echo json_encode([
            "channels" => "/channels",
            "liveChannels" => "/liveChannels",
	    ]);

    }
);


/**
 * Get all channels route.
 */
$f3->route('GET /channels',
   function() {

       $output = getChannels();

       header('Content-Type: application/json');
       echo json_encode($output);
   }
);

/**
 * Get only live channels route.
 */
$f3->route('GET /liveChannels',
    function() {

        $output = getChannels();

        header('Content-Type: application/json');
        echo json_encode($output['live']);
    }
);

$f3->run();

/**
 * Get all channel data from the database.
 *
 * @return array
 */
function getChannels() {
    $pdo = new PDO(DB_TYPE .":host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS)
    or die("No database connection");

    $query = $pdo->prepare(<<<SQL
    SELECT * FROM channel
SQL
);

    $query->execute();

    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    $output = [];

    foreach($results as $result) {
        if($result['live']) {
            $output['live'][] = json_decode($result['data']);
        } else {
            $output['notLive'][] = json_decode($result['data']);
        }

    }

    // Sort the live channels by viewer count
    usort($output['live'], 'cmp');

    return $output;
}

/**
 * Compare function used to sort channels by viewers.
 *
 * @param $a
 * @param $b
 * @return int
 */
function cmp($a, $b) {
	if($a->viewers == $b->viewers)
		return 0;
	
	if($a->viewers < $b->viewers)
		return 1;
	return -1;
}

