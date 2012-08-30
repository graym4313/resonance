$.ajaxSetup ({ cache: false });

var jwplayer;
var playerReady = function(obj) {
	jwplayer = $("#" + obj['id']).get(0);
	jwplayer.addModelListener("STATE", "playerState");

	$("a.track").on("click", playTrack);
};


$(function(){
  window.setInterval(function(){
    $("#main").load(window.location.href + " #main > tbody > tr", highlightCurrent);
  }, 1000 * 60 * 5); // refresh page every n minutes

  if (showPlayer)
    swfobject.embedSWF("player.swf", "player", "100%", "20", "9.0.0", null, {id:"player", type:"sound", backcolor:"000000", frontcolor:"FFFF00", file: "http://icecast.commedia.org.uk:8000/resonance_hi.mp3"}, {}, {id:"player", name:"player"});
});

var highlightCurrent = function(node){
  if (!node)
    node = findTrack(0);

  $(".playing").removeClass("playing");
  $(node).addClass("playing");
};

var playTrack = function(e){
  if (typeof e.button != "undefined")
    if ((!$.browser.msie && e.button !== 0) || ($.browser.msie && e.button !== 1)) // only allow left mouse button or programmatic trigger
      return true;

  e.preventDefault();

  highlightCurrent(this);

  jwplayer.sendEvent('LOAD', {file: this.href, duration: 1800});
  jwplayer.sendEvent('PLAY', this.href);

  return false;
};

var playerState = function(state){
  if (state.oldstate == "PLAYING" && state.newstate == "COMPLETED")
    nextTrack();
};

var nextTrack = function(){
  var next = findTrack(1);
  $(next).click();
};


var findTrack = function(next){
  var file = jwplayer.getPlaylist()[0].file;
  var tracks = $("#main a.track");

  for (var i = 0; i < tracks.length; i++)
    if (tracks.get(i).href == file)
      return tracks.get(i + next);
};

