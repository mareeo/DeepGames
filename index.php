<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, user-scalable=yes">
        <meta http-equiv="content-type" content="text/html" charset="utf-8" />
        <script src="jquery-2.1.0.min.js"></script>

        <link rel="stylesheet" href="js/jquery.mCustomScrollbar.css" />
        <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
        
        <script src="js/stuff.js"></script>
        <script src="js/handlebars-v2.0.0.js"></script>
        <link rel="stylesheet" type="text/css" href="css/stuff.css" />
        <link rel="stylesheet" type="text/css" href="css/normalize.css" />
        <link rel="stylesheet" type="text/css" href="css/font-awesome-4.1.0/css/font-awesome.min.css" />

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
           <button class='flat-button'><i class="fa fa-video-camera"></i> Popout Player</button>
           <button class='flat-button'><i class="fa fa-comment"></i> Popout Chat</button>
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

            
            

    </body>
</html>
