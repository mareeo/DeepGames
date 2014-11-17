<?php


//Sanity Check
if (!isset($_GET['channel']) )
   exit("Please specify and channel");

//Get GET parameters
$CHANNEL = $_GET['channel'];

?>

<!DOCTYPE html>
<!--
 _____                     _____
|  __ \                   / ____|
| |  | | ___  ___ _ __   | |  __  __ _ _ __ ___   ___  ___
| |  | |/ _ \/ _ \ '_ \  | | |_ |/ _` | '_ ` _ \ / _ \/ __|
| |__| |  __/  __/ |_) | | |__| | (_| | | | | | |  __/\__ \
|_____/ \___|\___| .__/   \_____|\__,_|_| |_| |_|\___||___/
                 | |
                 |_|    Live Video Game Streaming
-->
<html>
<head>
   <title>Deep Games &raquo; Popout player</title>

   <meta name="description" content="Live video game with viewer interaction!  We play everything from the newest released to the classics." />
   <meta name="keywords" content="live video game streaming stream feed Nintendo Wii Sony Playstation 3 PS3 Microsoft Xbox 360 Kinect Move Starcraft 2" />
   <meta http-equiv="content-type" content="text/html" charset="utf-8" />

   <link rel="stylesheet" type="text/css" href="css/stuff.css" charset="utf-8" />

   <link rel="shortcut icon" href="http://www.deepgamers.com/images/favicon.ico" />

   <script src="jquery-2.1.0.min.js"></script>
   <script type="text/javascript" src="js/jquery-ui.min.js"></script>
   <link type="text/css" href="css/ui-lightness/jquery-ui-1.8.24.custom.css" rel="Stylesheet" />
   <script>
      <?php echo 'currentChannel = "'.$CHANNEL.'";'; ?>
   </script>
   <script type="text/javascript" src=js/swfobject.js></script>
   <script type="text/javascript" src=js/lsPlayer.js></script>
   <link rel="stylesheet" type="text/css" href="css/font-awesome-4.1.0/css/font-awesome.min.css" />
   <link rel='stylesheet' type='text/css' href='http://fonts.googleapis.com/css?family=Open+Sans' />

   <style>

      body {
         background: #000000;
         position: relative;
         color: white;
      }

      body, input, button {
         font-family: "Open Sans", "sans-serif";
      }
      html, body {
         margin: 0px;
         padding: 0px;
         overflow: hidden;
      }

      #footer {
         overflow: auto;
         padding: 0px 3px;
         font-size: 12px;
         line-height: 25px;
      }

      #buttons {
         float: left;
         margin-right: 20px;
      }

      #now-playing {
         text-align: right;
         white-space: nowrap;
         overflow: hidden;
      }

      #viewers {
         text-align: left;
         float: left;
         margin-right: 10px;
      }

      #volumeSlider {
         display: inline-block;
         width: 75px;
         margin-left: 6px;
      }

   </style>

</head>
<body>
   <div id="container">
      <div id="ls-player"></div>
   </div>
   <div id="footer">
      <div id="buttons">
         <button id="pause" class="flat-button"><span class='fa fa-pause'></span> Pause</button>
         <button id="mute" class="flat-button"><span class='fa fa-volume-up'></span> Mute</button>
         <div id="volumeSlider"><div id="slider"></div></div>
      </div>
      <div id="viewers"></div>
      <div id="now-playing"></div>
   </div>
</body>
</html>
