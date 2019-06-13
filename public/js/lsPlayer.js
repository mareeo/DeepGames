
/* Global variable declarations */

var paused = false;           // Pause status of the Livestream player
var muted = false;			// Mute status of the Livestream player
var updateInterval;           // The interval number for the updateNowPlaying()
var loadedPlayer;			// The current loaded player. EX "livestream" or "twitchtv"
var player;				// HTML element for the player
var chat;					// HTML elemefnt for the chat
var lsplayer;				// HTML element for the Livestream player
var chatWidth = 300;		// Width of the chat in pixels
var live;
var autoSwitchAttempt = 0;

//Parameters to pass with swfobject
var params = { AllowScriptAccess: 'always', allowfullscreen: 'true' };
var flashvars = { channel: currentChannel };

$(document).ready(function (){
	
	// If Chrome make it think we always have flash player installed
	// https://github.com/swfobject/swfobject/issues/57
	if(window.chrome)
		swfobject.ua.pv = [100, 0, 0];  // we have FP v100, hoorray
		
	swfobject.embedSWF("https://cdn.livestream.com/chromelessPlayer/v20/playerapi.swf", "ls-player", "100%", "100%", "9.0.0", "expressInstall.swf", flashvars, params);
	
   
   
   
   
   
   window.onresize = fitPlayer;
	
	fitPlayer();
   
   $("#pause").click(function() {
      pause();
   })
   
   $("#mute").click(function() {
      mute();
   })
   
   //Set up the volume slider
	$( "#slider" ).slider({
			range: "min",
			value: 75,
			min: 0,
			max: 1,
			step: 0.1,
			slide: function( event, ui ) {
				lsplayer.setVolume(ui.value);
			}
	});
   
   
});



/* * * * * * Functions dealing with the page layout and contents * * * * * */





// Callback for when the livestream player is loaded
function livestreamPlayerCallback(event) {
	if (event == 'ready') {
		//alert("Callback of player for channel" + currentChannel.channel);
		lsplayer = document.getElementById("ls-player");
		lsplayer.load(currentChannel);
		updateNowPlaying();
		updateInterval = setInterval("updateNowPlaying()", 5000);
		lsplayer.showFullscreenButton(true);
		lsplayer.showPlayButton(false);
		lsplayer.showPauseButton(false);
		lsplayer.showMuteButton(false);
		
		// Only start playback if they didn't have it paused.
		if(!paused)
			lsplayer.startPlayback();
		
		// If they had the player muted we want to keep it muted.
		if(muted)
			lsplayer.toggleMute();
	}
}

//Replaces special characters in a string and returns it
function htmlspecialchars(str) {
	if (typeof(str) == "string") {
		str = str.replace(/&/g, "&amp;"); /* must do &amp; first */
		str = str.replace(/"/g, "&quot;");
		str = str.replace(/'/g, "&#039;");
		str = str.replace(/</g, "&lt;");
		str = str.replace(/>/g, "&gt;");
	}
	return str;
}

//Updates the 'now-playing' element with the current streamer
function updateNowPlaying() {
	
	var playing = "";
	
		
		if (lsplayer.isLive())
			playing = '<b>Now <span style="color: #FF0000">LIVE</span>: </b>';
		else
			playing = '<b>Now Playing: </b>';
		playing += lsplayer.getCurrentContentTitle();
		
		var viewers = " <b>Viewers:</b> " + lsplayer.getViewerCount();
		
   
	
	// Put the results in the now-playing element
	$("#now-playing").html(playing);
	
	$("#viewers").html(viewers);
}

// Toggle the pausing of the Livestream player
function pause() {
	
	if (paused) {
		lsplayer.startPlayback();
		$("#pause").html("<span class='fa fa-pause'></span> Pause");
		paused = false;
	} else {
		lsplayer.stopPlayback();
		$("#pause").html("<span class='fa fa-play'></span> Play");
		paused = true;
	}
}

// Toggle the muting of the livestream player
function mute() {
	// Toggle the mute
	lsplayer.toggleMute();
	
	// Update the button
	if (lsplayer.isMute()) {
		$("#mute").html("<span class='fa fa-volume-off'></span> Unmute");
		$("#slider").slider( "option", "disabled", true );
		muted = true;
	} else {
		$("#mute").html("<span class='fa fa-volume-up'></span> Mute");
		$("#slider").slider( "option", "disabled", false );
		muted = false;
	}
}
function fitPlayer() {
   containerHeight = $(window).height() - $("#footer").height();
   $("#container").height(containerHeight);
}

