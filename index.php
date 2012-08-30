<?php
header('Cache-Control: no-cache, must-revalidate');
require 'Resonance.php';
$r = new Resonance;
list($programmes, $future) = $r->load_files();
?>
<html>
<head>
  <meta charset="UTF-8">
  <title>Resonance104.4fm archive</title>
  <link href='http://fonts.googleapis.com/css?family=Nunito:regular,bold' rel='stylesheet' type='text/css'>
  <link href='http://fonts.googleapis.com/css?family=Cabin+Sketch:bold' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="style.css">
  <link rel="shortcut icon" href="favicon.ico">
</head>

<body>
  <div id="player"></div>
  <div id="header"><a class="track" href="http://icecast.commedia.org.uk:8000/resonance_hi.mp3">Listen live to Resonance104.4fm</a></div>

  <table id="main">
    <tr>
      <td class="listing">
          <? foreach ($programmes as $item) $r->print_programme($item, false); ?>
      </td>
      <td class="listing" id="future">
          <? $r->old_date = null; foreach ($future as $item) $r->print_programme($item, true); ?>
      </td>
    </tr>
    <tr id="footer">
      <td colspan="5">
        <a href="http://resonancefm.com/">Resonance104.4fm</a>
      </td>
    </tr>
  </table>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/swfobject/2/swfobject.js"></script>

  <?php if ((stripos($_SERVER['HTTP_USER_AGENT'], 'iphone') === FALSE) && (stripos($_SERVER['HTTP_USER_AGENT'], 'songbird') === FALSE)): ?>
    <script>var showPlayer = true;</script>
  <?php endif; ?>

  <script src="player.js"></script>

</body>
</html>

