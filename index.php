<!DOCTYPE html>
<html>
<head>
   <meta name="viewport" content="width=device-width, user-scalable=yes">
   <meta http-equiv="content-type" content="text/html" charset="utf-8" />
   <script src="js/jquery-2.1.1.min.js"></script>
   <script src="js/humane.min.js"></script>

   <script src="js/stuff.js"></script>
   <script src="js/handlebars-v2.0.0.js"></script>

   <link rel="stylesheet" type="text/css" href="css/font-awesome-4.1.0/css/font-awesome.min.css" />
   <link rel="stylesheet" type="text/css" href="css/themes/jackedup.css" />
   <link rel="stylesheet" type="text/css" href="css/stuff.css" />
   <link rel="stylesheet" type="text/css" href="css/normalize.css" />


   <!--BEGIN CLIENT SIDE TEMPLATES-->
   <script id="selector-template" type="text/x-handlebars-template">
      <div class="one-half">
         <div class="selector" id="{{service}}-{{name}}">
            <div class="thumbnail"><img src="{{thumbnail}}" /></div>
            <div class="info">
               <span class="name">{{name}}</span>{{#if game}} playing <span class="game">{{game}}</span>{{/if}}<br>
               <span class="title">{{title}}</span>
            </div>
            <div class="viewers">
               {{viewers}} viewers
            </div>
            <div class="serviceLogo">
               <img src="images/{{service}}-logo.png" class="serviceLogo" />
            </div>
         </div>
      </div>
   </script>

   <script id="loading-template" type="text/x-handlebars-template">
      <div class="loading">
         <img src="images/loading.gif" /><br>Loading...
      </div>
   </script>

   <script id="offlineSelector-template" type="text/x-handlebars-template">
      <div class="one-third">
         <div class="selector" id="{{service}}-{{name}}">
            <div class="info">
               <span class="name">{{name}}</span><br>
               <span class="title">{{title}}</span>
            </div>
            <div class="viewers">
               {{viewers}} viewers
            </div>
            <div class="serviceLogo">
               <img src="images/{{service}}-logo.png" class="serviceLogo" />
            </div>
         </div>
      </div>
   </script>

   <script id="loading-template" type="text/x-handlebars-template">
      <div class="loading">
         <img src="images/loading.gif" /><br>Loading...
      </div>
   </script>

   <script id="config-dialog" type="text/x-handlebars-template">
      <div id="configDialog">
         <input type="checkbox" id="autoSwitchCheck" {{#autoSwitch}}checked{{/autoSwitch}}/><label for="autoSwitchCheck"> Enable auto switch</label><br>
         <input type="checkbox" id="liveChannelNoteCheck" {{#liveChannelNotification}}checked{{/liveChannelNotification}}/><label for="liveChannelNoteCheck"> Enable visual notification for new live channels</label><br>
         <input type="checkbox" id="liveToneCheck" {{#liveChannelTone}}checked{{/liveChannelTone}}/><label for="liveToneCheck"> Enable live channel sound</label><br>
         Live channel sound volume<br>
         <i class="fa fa-volume-off"></i> <input id="toneVolume" type="range" min="0" max="1" step="0.05" value="{{toneVolume}}"style="height:11px; margin: 1px;"/> <i class="fa fa-volume-up"></i><br>
         <button class="flat-button" id="saveButton" style="width: 100%">Save</button>
      </div>
   </script>
   <!--END CLIENT SIDE TEMPLATES-->



</head>
   <body>
      <?php include("header.php"); ?>
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
            <h2>Live Channels</h2>
            <div id="liveSelectors"></div>
         </div>
         <button id="showOfflineButton" class='flat-button' >Show Offline Channels</button>
         <div id="offlineChannels">
            <h2>Live Community Channels</h2>
            <div id="offlineSelectors"></div>
         </div>
      </div>
   <audio id="tone">
      <source="incomingGame.ogg" />
   </audio>




   </body>
</html>
