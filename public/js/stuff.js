/*
 *Woo!  All new code!
 *I'm just maintaining that I suck at Javascript.  I need to learn how objects work.
 *Maybe once then I'll be able to write code that doesn't make me feel dirty.
 *Maybe that just isn't possible when dealing with DOM manipulation.
 */


var currentChannel = {};
var isFirstLoad = true;
var initialTimeout;

var channelData = {
   angelthump: {},
   twitch: {}
};

var Config = {
   autoSwitch: false,
   liveChannelNotification: true,
   liveChannelTone: false,
   toneVolume: 0.5,


   save: function() {
      localStorage.setItem('autoSwitch', this.autoSwitch);
      localStorage.setItem('liveChannelNotification', this.liveChannelNotification);
      localStorage.setItem('liveChannelTone', this.liveChannelTone);
      localStorage.setItem('toneVolume', this.toneVolume);
   },

   load: function() {
      var stored = localStorage.getItem('autoSwitch');
      if(stored !== null)
         this.autoSwitch = stored;

      stored = localStorage.getItem("liveChannelNotification");
      if(stored !== null)
         this.liveChannelNotification = stored;

      stored = localStorage.getItem("liveChannelTone");
      if(stored !== null)
         this.liveChannelTone = stored;

      stored = localStorage.getItem("toneVolume");
      if(stored !== null)
         this.toneVolume = stored;

   }


};

setInterval("getTeam()", 70000);

/*
 * Set up everything on document ready
 */
$(function () {

   Config.load();



   getTeam();



   $("#toggle").click(function () {
      toggleMenu();
   });

   $("#overlay").click(function () {
      toggleMenu();
   });

   $("#toggleSide").click(function () {
      toggleMenu();
   });

   $("#toggleChat").click(function () {
      toggleChat();
   });

   var configTemplate = Handlebars.templates.configDialog;

   $("#settingsButton").click(function(event) {

      if($("#configDialog").length > 0) {
         $("#configDialog").remove();
         return;
      }

      $("#configDialog").remove();


      var html = $(configTemplate(Config));

      html.find("#saveButton").click(function() {
         Config.autoSwitch = $("#autoSwitchCheck").is(":checked");
         Config.liveChannelNotification = $("#liveChannelNoteCheck").is(":checked");
         Config.liveChannelTone = $("#liveToneCheck").is(":checked");
         Config.toneVolume = $("#toneVolume").val();
         Config.save();
         $("#configDialog").remove();

      });



      $('body').append(html);
      $("#configDialog").css("top", event.pageY);
      $("#configDialog").css("left",event.pageX - $("#configDialog").width()-20);

      fitPlayer();



   });

   $(".deepSelector").click(function (event) {

      console.debug(event.target);

      var channel = $(this).attr('id').split('-');
      changeChannel(channel[0], channel[1]);
      $("html, body").animate({
         scrollTop: $("#player").offset().top
      }, "slow");

   });

   $("#showOfflineButton").click(function(event) {
      $("#offlineChannels").toggle();

      if($("#offlineChannels").css('display') == 'none') {
         $("#showOfflineButton").html('Show Offline Channels');
      } else {
         $("#showOfflineButton").html('Hide Offline Channels');
      }
      fitPlayer();

   });

   $("#popoutChat").click(function() {
      popoutChat();
   });
   $("#popoutPlayer").click(function() {
      popoutPlayer();
   });


   fitPlayer();
   window.onresize = fitPlayer;

});

$(window).load(function() {
   fitPlayer();
})

/*
 * Get the Hitbox.tv team info
 */
function getTeam() {
   $.ajax({
      type: "GET",
      dataType: "json",
      url: "api/channels",

      beforeSend: function() {
         if(isFirstLoad) {
            var loadingTemplate = Handlebars.templates.loading;
            var html = loadingTemplate();
            $("#player").html(html);
            $("#liveSelectors").html(html);

         }
      },

      //Once we have the info
      success: function (data) {
         var newlyLiveChannels = processData(data);
         showTeam(data);
         if (isFirstLoad) {
            isFirstLoad = false;
            pickFirstChannel();
         } else {
            if(newlyLiveChannels.length > 0) {
               if(Config.liveChannelNotification) {
               }

               if(Config.liveChannelTone) {
                  playSound();
               }
            }
         }
         fitPlayer();
      }
   });
}


/**
 * Process channel data
 * @param data
 */
function processData(data) {

   // Copy the old channel data
   var oldData = jQuery.extend(true, {}, channelData);
   var newlyLive = [];

   channelData = {
      angelthump: {},
      twitch: {}
   };

   // For every live channel
   $.each(data.live, function (index, channel) {

      // If we didn't have data for this channel before, it's newly live
      if(!(channel.name in oldData[channel.service])) {
         console.error("Data doesn't exists for " + channel.service + channel.name);
         newlyLive.push(channel.name);

      // If we did have data for this channel before and it wasn't live, it's newly live
      } else if (!oldData[channel.service][channel.name].live == true) {
         console.error("Data existed but wasn't live for" + channel.service + channel.name);
         newlyLive.push(channel.name);
      }

      // Update our local data
      channelData[channel.service][channel.name] = channel;

      // If this is the current channel, update the currentChannel variable.
      if(channel.name == currentChannel.name && channel.service == currentChannel.service) {
         currentChannel = channel;
      }


   });

   // For every non-live channel
   $.each(data.notLive, function (index, channel) {

      // Update our local data
      channelData[channel.service][channel.name] = channel;

      // If this is the current channel, update the currentChannel variable.
      if(channel.name == currentChannel.name && channel.service == currentChannel.service) {
         currentChannel = channel;
      }
   });

   return newlyLive;

}

/**
 * Play a sound for a newly live channel
 * @param data
 */
function playSound() {
      var snd = new Audio("incomingGame.ogg");
      snd.volume = Config.toneVolume;
      //snd.play();
}


/*
 * Update the sidemenu with the new Hitbox team information
 */
function showTeam() {

   var liveDiv = $("#liveSelectors");
   var offDiv = $("#offlineSelectors");

   // Compile templates
   var liveTemplate = Handlebars.templates.selector;
   var offlineTemplate = Handlebars.templates.offlineSelector;

   // Empty existing divs
   liveDiv.empty();
   offDiv.empty();

   // For every service
   $.each(channelData, function(service, channels) {

      // For every channel
      $.each(channels, function(index, channel) {
         var html;
         var targetDiv;

         // Render the appropriate template based upon live status
         if(channel.live == true) {
            html = liveTemplate(channel);
            targetDiv = liveDiv;
         } else {
            html = offlineTemplate(channel);
            targetDiv = offDiv;
         }

         // Attach the channel data to the div
         html = $(html);
         var selectorDiv = $(html.children()[0]);
         selectorDiv.data("channel", channel);

         // Add the "current" class if this is the current channel
         if(channel == currentChannel) {
            selectorDiv.addClass("current");
         }

         // Add the newly created element
         targetDiv.append(html);
      });

   });

   // Create and bind the click event for channel selectors
   $(".selector").click(function (event) {

      // Get the div of the channel selector
      var targetDiv = $(event.target).closest('div.selector');

      // Get channel data attached to the div
      var channelData = targetDiv.data('channel');

      // Change the channel
      var success = changeChannel(channelData);

      // If it was changed successfully, update the current channel
      if(success) {
         $(".current").removeClass('current');
         targetDiv.addClass('current');
      } else {
         console.error("Error changing channel!");
      }

   });
}


/*
 * Changes the player and chat to the channel passed.
 *
 * Returns true on success, false on failure
 */
function changeChannel(channelData) {

   // Update currentChannel
   currentChannel = channelData;

   // Remove the old current, and set this channel's selector to current
   var divName = currentChannel.service + "-" + currentChannel.name;
   $(".current").removeClass('current');
   $("#"+divName).addClass('current');

   var playerCode, chatCode;
   let deepChatChannels = ['gamesdonequick', 'nintendo', 'twitch'];
   let hostname = window.location.hostname;

   if (currentChannel.service === 'twitch') {
      playerCode = `<iframe src="https://player.twitch.tv/?channel=${currentChannel.name}&parent=${hostname}" frameborder="0" allowfullscreen="true" scrolling="no" height="100%" width="100%"></iframe>`;
      if (deepChatChannels.includes(currentChannel.name)) {
         chatCode = `<iframe src="https://www.twitch.tv/embed/deepgamers/chat?darkpopout&parent=${hostname}" frameborder="0" scrolling="no" height="100%" width="100%"></iframe>`
      } else {
         chatCode = `<iframe src="https://www.twitch.tv/embed/${currentChannel.name}/chat?darkpopout&parent=${hostname}" frameborder="0" scrolling="no" height="100%" width="100%"></iframe>`
      }
   } else if (currentChannel.service === 'angelthump') {
      playerCode = `<iframe src="https://angelthump.com/${currentChannel.name}/embed" frameborder="0" allowfullscreen="true" scrolling="no" height="100%" width="100%"></iframe>`;

      if (currentChannel.name === 'hehe') {
         chatCode = `<iframe src="https://www.twitch.tv/embed/hehefunnys/chat?darkpopout&parent=${hostname}" frameborder="0" scrolling="no" height="100%" width="100%" ></iframe>`
      } else {
         chatCode = `<iframe src="https://www.twitch.tv/embed/deepgamers/chat?darkpopout&parent=${hostname}" frameborder="0" scrolling="no" height="100%" width="100%" ></iframe>`
      }
   }

   // Update the player and chat code
   $("#player").html(playerCode);
   $("#chat").html(chatCode);

   return true;
}

/*
 * Called on page resize to fit the player
 */
function fitPlayer() {


   var initialPageWidth = $(window).width();

   // Get player width.  If page is less than 640 pixels, don't include chat in calculation
   if ($(window).width() < 640) {
      $("#chat").hide();
      var width = $("#topContainer").width();
   } else {
      $("#chat").show();
      var width = $("#topContainer").width() - $("#chat").width();
   }

   // Calculate player height
   var newHeight = Math.floor(width / 16 * 9) + 25;
   var maxHeight = $(window).height() - 75;

   // Don't let the player get too tall
   if (newHeight > maxHeight) {
      newHeight = maxHeight;
   }

   //Don't let the player get too short
   if (newHeight < 300) {
      newHeight = 300;
   }

   // Resize the elements
   $("#player").height(newHeight);
   $("#leftContainer").width(width);
   $("#chat").height(newHeight);

   // Scrollbars appearing ruins everything.  If adjusting the player width caused a change in the width of
   // the page (meaning the scroll bars either appeared or disappeared), compensate for this by adjusting the
   // width of the player again.
   var difference = initialPageWidth - $(window).width();

   if(difference != 0) {
      $("#leftContainer").width(width - difference);
   }

}

/*
 * If multiple Deep channels are live, this will present an option
 * Only called on the initial page load
 */
function pickFirstChannel() {

   var liveChannels = getLiveChannels();

   if(liveChannels.length > 0) {
      changeChannel(liveChannels[0]);
   } else {
      // $("#player").html("No channels live");
      changeChannel(channelData['angelthump']['dman99']);
   }

}

function toggleChat() {
   if ( $("#chat").width() == 0) {
      showChat();
   } else {
      hideChat();
   }
   fitPlayer();
}

function showChat() {
   $("#chat").show();
   $("#chat").width(300);
}

function hideChat() {
   $("#chat").hide();
   $("#chat").width(0);
}

function getLiveChannels() {
   var liveChannels = [];

   $.each(channelData, function(service, data) {
      $.each(data, function(index, channelData) {
         if(channelData.live == true) {
            liveChannels.push(channelData);
         }
      });
   });



   liveChannels.sort(function(a,b) { return b.viewers - a.viewers } );

   return liveChannels;

}

function popoutChat() {
   channel = currentChannel.name.toLowerCase();
   if(currentChannel.service == "livestream") {
      window.open (
         "http://cdn.livestream.com/chat/LivestreamChat.swf?&channel="+channel,
         "",
         "status=0,toolbar=0,location=0,scrollbars=0,height=600,width=350"
      );

   } else if(currentChannel.service == "twitch") {
      window.open (
         "http://twitch.tv/"+channel+"/chat",
         "",
         "status=0,toolbar=0,location=0,scrollbars=0,height=600,width=350"
      );
   } else if (currentChannel.service == "hitbox") {
      window.open (
         "http://www.hitbox.tv/embedchat/"+channel+"?autoconnect=true",
         "",
         "status=0,toolbar=0,location=0,scrollbars=0,height=600,width=350"
      );
   }
}

function popoutPlayer() {
   channel = currentChannel.name.toLowerCase();
   if(currentChannel.service == "livestream") {
      window.open (
         "lsPlayer.php?channel="+$channel,
         "",
         "status=0,toolbar=0,location=0,scrollbars=0,height=480,width=840"
      );

   } else if(currentChannel.service == "twitch") {
      window.open (
         "http://www.twitch.tv/widgets/live_embed_player.swf?channel="+$channel,
         "",
         "status=0,toolbar=0,location=0,scrollbars=0,height=480,width=840"
      );
   } else if (currentChannel.service == "hitbox") {
      window.open (
         "http://hitbox.tv/#!/embed/"+channel+"?autoplay=true",
         "",
         "status=0,toolbar=0,location=0,scrollbars=0,height=480,width=840"
      );
   }
}
