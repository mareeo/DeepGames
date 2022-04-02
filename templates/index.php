<?php
use League\Plates\Extension\RenderContext\RenderContext;

/** @var $this RenderContext */

?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, user-scalable=yes">
    <meta http-equiv="content-type" content="text/html" charset="utf-8" />
    <script src="/js/jquery-2.1.1.min.js"></script>
    <script src="/js/humane.min.js"></script>

    <link rel="shortcut icon" href="favicon.ico" />

    <script src="/js/stuff.js"></script>
    <script src="/js/handlebars.runtime.min.js"></script>
    <script src="/js/templates.js"></script>

    <link rel="stylesheet" type="text/css" href="css/font-awesome-4.1.0/css/font-awesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/themes/jackedup.css" />
    <link rel="stylesheet" type="text/css" href="css/stuff.css" />
    <link rel="stylesheet" type="text/css" href="css/normalize.css" />
</head>
<body>
<?php $this->insert('header'); ?>
<div id="topContainer">
    <div id="leftContainer">
        <div id="player"></div>
    </div>
    <div id="chat"></div>
    <div style="clear:both;"></div>
</div>
<div style="text-align: right;">
    <button class='flat-button' id="toggleChat"><i class="fa fa-caret-square-o-right"></i> Toggle Chat</button>
    <button class='flat-button' id="popoutPlayer"><i class="fa fa-video-camera"></i> Popout Player</button>
    <button class='flat-button' id="popoutChat"><i class="fa fa-comment"></i> Popout Chat</button>
    <button class='flat-button' id="settingsButton"><i class="fa fa-cog"></i> Change Settings</button>
</div>
<div id="content">
    <div id="liveChannels">
        <h2>Live Community Channels</h2>
        <div id="liveSelectors"></div>
    </div>
<!--    <button id="showOfflineButton" class='flat-button' >Show Offline Channels</button>-->
    <div id="offlineChannels">
        <h2>Offline Community Channels</h2>
        <div id="offlineSelectors"></div>
    </div>
</div>
<audio id="tone">
    <source="incomingGame.ogg" />
</audio>




</body>
</html>
