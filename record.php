<?php

require 'Resonance.php'; // first, as sets timezone

$date = date('Y-m-d_Hi');  // set here, as tagging files can take a little while

$r = new Resonance;
$r->tag_files();

// ignore morning repeats between 1am and 11am
$hour = date('G');
if ($hour > 0 && $hour < 11) {
    exit();
}

$r->start_recording($date);

