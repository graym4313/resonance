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
  <link href='https://fonts.googleapis.com/css?family=Nunito:regular,bold' rel='stylesheet' type='text/css'>
  <link href='https://fonts.googleapis.com/css?family=Cabin+Sketch:bold' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <object id="player"></object>

  <div id="header"><a class="track" href="http://54.77.136.103:8000/resonance">Listen live to Resonance104.4fm</a></div>

  <table id="main">
    <tr>
      <td class="listing">
          <? foreach ($programmes as $item) $r->print_programme($item, false); ?>
      </td>
      <td class="listing" id="future">
          <? $r->oldDate = null; foreach ($future as $item) $r->print_programme($item, true); ?>
      </td>
    </tr>
    <tr id="footer">
      <td colspan="5">
        <a href="http://resonancefm.com/">Resonance104.4fm</a>
      </td>
    </tr>
  </table>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
  <script src="player.js"></script>
</body>
</html>


