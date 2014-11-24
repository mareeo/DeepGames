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
   livestream: {},
   twitch: {},
   hitbox: {}
};

setInterval("getTeam()", 70000);

/*
 * Set up everything on document ready
 */
$(function () {



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
            var loadingTemplate = Handlebars.compile($("#loading-template").html());

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
               var message = makeNotificationMessage(newlyLiveChannels);
               notify(message);
               playSound();
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
      livestream: {},
      twitch: {},
      hitbox: {}
   };

   // For every live channel
   $.each(data.live, function (index, channel) {

      // If we didn't have data for this channel before, it's newly live
      if(!(channel.name in oldData[channel.service])) {
         console.log("Data doesn't exists for " + channel.service + channel.name);
         newlyLive.push(channel.name);

      // If we did have data for this channel before and it wasn't live, it's newly live
      } else if (!oldData[channel.service][channel.name].live) {
         console.log("Data existed but wasn't live for" + channel.service + channel.name);
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
      snd.volume = 0.5;
      snd.play();
}


/*
 * Update the sidemenu with the new Hitbox team information
 */
function showTeam() {

   var liveDiv = $("#liveSelectors");
   var offDiv = $("#offlineSelectors");

   // Compile templates
   var liveTemplate = Handlebars.compile($("#selector-template").html());
   var offlineTemplate = Handlebars.compile($("#offlineSelector-template").html());

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
         if(channel.live) {
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

   // Do nothing if changing to the current channel
   if(currentChannel == channelData) {
      return false;
   }

   // Update currentChannel
   currentChannel = channelData;

   // Remove the old current, and set this channel's selector to current
   var divName = currentChannel.service + "-" + currentChannel.name;
   $(".current").removeClass('current');
   $("#"+divName).addClass('current');

   // Update the player and chat code
   $("#player").html(currentChannel.player_code);
   $("#chat").html(currentChannel.chat_code);

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

   console.log(liveChannels);

   if(liveChannels.length > 0) {
      changeChannel(liveChannels[0]);
   } else {
      changeChannel(channelData['livestream']['deepgamers']);
   }

}

/*
 *If multiple channels are live, this determines which one to load
 */
function determineFirstChannel() {

   var channel;
   var maxViewers = -1;

   if (channelData['livestream']['deepgamers'].live) {
      maxViewers = channelData['livestream']['deepgamers'].viewers;
      channel = 'livestream-deepgamers';
   }

   if (channelData['hitbox']['deepgames'].live) {
      if (channelData['hitbox']['deepgames'].viewers > maxViewers) {
         maxViewers = channelData['hitbox']['deepgames'].viewers;
         channel = 'hitbox-deepgames';
      }
   }

   if (channelData['twitch']['deepgamers'].live) {
      if (channelData['twitch']['deepgamers'].viewers > maxViewers) {
         maxViewers = channelData['twitch']['deepgamers'].viewers;
         channel = 'twitch-deepgamers';
      }
   }

   return channel;
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
         if(channelData.live) {
            liveChannels.push(channelData);
         }
      });
   });



   liveChannels.sort(function(a,b) { return b.viewers - a.viewers } );

   return liveChannels;

}


function makeNotificationMessage(channels) {
   if(channels.length == 1) {
      return channels[0] + " just went live!";
   }

   if(channels.length == 2) {
      return channels[0] + " and " + channels[1] + " just went live!";
   }

   var message = '';
   for(i=0; i<channels.length-1; i++) {
      message += channels[i] + ", ";
   }

   message += "and " + channels[channels.length-1] + " just went live!";

   return message;
}

function notify(text) {
   //$.notify(text, {
   //   globalPosition: 'top center',
   //   autoHide: false,
   //   className: "deepNotification"
   //});

   humane.log(text, {
      timeout: 7500,
      clickToClose: true,
      addnCls: 'deepNotification'
   });
}


