/*
 *Woo!  All new code!
 *I'm just maintaining that I suck at Javascript.  I need to learn how objects work.
 *Maybe once then I'll be able to write code that doesn't make me feel dirty.
 *Maybe that just isn't possible when dealing with DOM manipulation.
 */


var currentChannel = {};
var initialLoad = false;
var initialTimeout;

var channelData = {
   livestream: {},
   twitch: {},
   hitbox: {}
};

setInterval("getTeam()", 90000);

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
         if(!initialLoad) {
            var source   = $("#loading-template").html();
            var template = Handlebars.compile(source);

            var html = template(source);
            $("#player").html(html);
            $("#liveSelectors").html(html);

         }
      },

      //Once we have the info
      success: function (data) {
         showTeam(data);
         if (!initialLoad) {
            initialLoad = true;
            pickFirstChannel();
         }
      }
   });
}

/*
 * Update the sidemenu with the new Hitbox team information
 */
function showTeam(data) {


   var liveDiv = $("#liveSelectors");
   liveDiv.empty();

   var offDiv = $("#offlineSelectors");
   offDiv.empty();

   var source   = $("#selector-template").html();
   var template = Handlebars.compile(source);

   var source2 = $("#offlineSelector-template").html();
   var template2 = Handlebars.compile(source2);



   $.each(data.live, function (index, channel) {

      channelData[channel.service][channel.name] = channel;






      var html = template(channel);
      html = $(html);

      var selectorDiv = $(html.children()[0]);

      selectorDiv.data("channel", channel);

      if(channel.name == currentChannel.name && channel.service == currentChannel.service) {
         currentChannel = channel;
         selectorDiv.addClass("current");
      }


      liveDiv.append(html);

   });

   if (data.live.length == 0) {
      liveDiv.append("No live channels...");
   }

   $.each(data.notLive, function (index, channel) {

      channelData[channel.service][channel.name] = channel;

      var html = template2(channel);
      html = $(html);

      var selectorDiv = $(html.children()[0]);

      selectorDiv.data("channel", channel);

      if(channel.name == currentChannel.name && channel.service == currentChannel.service) {
         currentChannel = channel;
         selectorDiv.addClass("current");
      }


      offDiv.append(html);


   });



   $(".selector").click(function (event) {

      var targetDiv = $(event.target).closest('div.selector');
      targetDiv.addClass('current');


      console.log(targetDiv);

      var channelData = targetDiv.data('channel');

      $(".current").removeClass('current');
      targetDiv.addClass('current');

      console.log($(event.target));

      console.log(channelData);

      var channel = $(this).attr('id').split('-');
      changeChannel(channelData);

   });

}


/*
 * Changes the player and chat to the channel passed
 */
function changeChannel(channelData) {

   if(currentChannel.service == channelData.service &&
      currentChannel.channel == channelData.name) {
      return false;
   }

   currentChannel = channelData;

   var divName = currentChannel.service + "-" + currentChannel.name;

   $(".current").removeClass('current');
   $("#"+divName).addClass('current');

   console.log(divName);

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
