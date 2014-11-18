$.ajaxSetup ({ cache: false });

$(function(){
  window.setInterval(function(){
    $("#main").load(window.location.href + " #main > tbody > tr", highlightCurrent);
  }, 1000 * 60 * 5); // refresh page every n minutes
});

var highlightCurrent = function(node){
  if (!node) {
    node = findTrack(0);
  }

  $(".playing").removeClass("playing");
  $(node).addClass("playing");
};

var playTrack = function(e){
    // only allow left mouse button or programmatic trigger
    if (e.which !== 1) {
      return true;
    }

  e.preventDefault();

  highlightCurrent(this);

  $("#player").attr("data", this.href);

  return false;
};

var nextTrack = function(){
  var next = findTrack(1);

  $(next).click();
};

var findTrack = function(next){
  var file = $("#player").attr("data");

  var tracks = $("#main a.track");

  for (var i = 0; i < tracks.length; i++) {
    if (tracks.get(i).href == file) {
      return tracks.get(i + next);
    }
  }
};

$("#main").on("click", "a.track", playTrack);



