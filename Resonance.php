<?php

date_default_timezone_set('Europe/London');

class Resonance{
  public $now;
  private $dir;
  public $old_date;
  private $calendar = 'feed.xml';
  public $stream = 'http://icecast.commedia.org.uk:8000/resonance_hi.mp3';

  function __construct(){
    $this->now = time();
    $this->dir = dirname(__FILE__);
  }

  function start_recording($date = null){
    if (!$date) $date =  date('Y-m-d_Hi', $this->now);
    $file = sprintf($this->dir . '/audio/ResonanceFM_%s~.mp3', $date);
    $f = fopen($file, 'w');

    $ch = curl_init($this->stream);

    curl_setopt_array($ch, array(
      CURLOPT_CONNECTTIMEOUT => 60,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_FILE => $f,
    ));

    curl_exec($ch);
    fclose($f);
  }

  function tag_files(){
    $files = glob('audio/*~.mp3');
    if (empty($files))
      return;

    require_once('getid3/getid3.php');
    $getID3 = new getID3;
    $getID3->setOption(array('encoding'=> 'UTF-8'));
    require_once('getid3/write.php');

    $programmes = $this->load_calendar();

    foreach ($files as $file){
      //print "tagging $file\n";

      $date = $this->date_from_filename($file);

      $title = 0;
      foreach ($programmes as $item){
        if ($date >= $item['start'] && $date < $item['end']){
          $title = $item['title'];
          break;
        }
      }

      if (!$title)
        //$title = date('Y-m-d H:i', $date);
        $title = 'Resonance104.4fm';

      $show = str_replace(' (repeat)', '', $title);

      $episode = $show . ' ' . date('Y-m-d H:i', $date);// . '-' . date('H:i', $date + 60*30);

      //print "$show\n$episode\n";

      $output_file = str_replace('~.mp3', '.mp3', $file);

      $tagwriter = new getid3_writetags;
		  $tagwriter->filename = $file;
		  $tagwriter->tagformats = array('id3v1', 'id3v2.3');
		  $tagwriter->overwrite_tags = TRUE;
		  $tagwriter->tag_encoding = 'UTF-8';

      $tagwriter->tag_data = array(
        'artist' => array('Resonance104.4fm'),
        'album' => array($show),
        'title' => array($episode),
        'year' => array(date('Y', $date)),
        'comment' => array($item['content']),
      );

      if ($tagwriter->WriteTags())
        rename($file, $output_file);
      else
        print implode("\n", $tagwriter->errors) . implode("\n", $tagwriter->warnings);

      $dir = $this->dir . '/audio/shows/' . preg_replace('/[^\w\(\)\_\- ]/', '_', $title);
      if (!file_exists($dir))
        mkdir($dir, 0777, TRUE);

      symlink('../../' . basename($output_file), $dir . '/' . basename($output_file));
    }
  }

  function load_calendar(){
    $day = 60*60*24;

    $url = sprintf('http://www.google.com/calendar/feeds/schedule@resonancefm.com/public/full?start-min=%sT12:00:00&start-max=%s&orderby=starttime&sortorder=ascending&singleevents=true&ctz=Europe/London&max-results=1000',
      date('Y-m-d', $this->now - $day),
      date('Y-m-d', $this->now + $day * 2)
    );

    if (!file_exists($this->calendar))
      file_put_contents($this->calendar, file_get_contents($url));

    $dom = DOMDocument::load($this->calendar);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
    $xpath->registerNamespace('gd', 'http://schemas.google.com/g/2005');

    $items = array();

    foreach ($xpath->query('atom:entry') as $entry){
      $content = trim($xpath->query("atom:content[@type='text']", $entry)->item(0)->textContent);
      $content = str_replace('<BR>', '', $content);
      $content = preg_replace('/\n+/', "\n", $content);
      $content = preg_replace('/(email|web|presenter|producer|links): /i', "\n" . '$1: ', $content);

      $when = $xpath->query('gd:when', $entry)->item(0);

      $items[] = array(
        'title' =>$xpath->query("atom:title[@type='text']", $entry)->item(0)->textContent,
        'content' => $content,
        'start' => strtotime($when->getAttribute('startTime')),
        'end' => strtotime($when->getAttribute('endTime')),
      );
    }

    return $items;
  }

  function make_link($matches){
    $url = $matches[0];
    return sprintf('<a href="%s">%s</a>', htmlspecialchars($url), htmlspecialchars($url));
  }

  function make_link_www($matches){
    $url = trim($matches[0]);
    return sprintf(' <a href="%s">%s</a>', htmlspecialchars('http://' . $url), htmlspecialchars($url));
  }

  function format_file_size($bytes) {
    $text = array('bytes', 'kb', 'MB', 'GB', 'TB', 'PB');
    $item = floor(log($bytes)/log(1024));

    return sprintf('%d '. $text[$item], ($bytes/pow(1024, floor($item))));
  }

  function date_from_filename($file){
    preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2})(\d{2})/', basename($file), $matches);
    list($match, $date, $hour, $min) = $matches;

    return strtotime(sprintf('%sT%d:%d', $date, $hour, $min));
  }

  function print_programme($item, $future = FALSE){
    if (!$future && empty($item['files']))
      return;

    $start = $item['start'] - 60*60*2; // day ends at 2am
    $date = date('Y-m-d', $start);
    if ($date != $this->old_date){
      if ($this->old_date && !$future)
        print '</td><td class="listing">';
      printf('<h2>%s</h2>', date('l, j F', $start));
    }
    $this->old_date = $date;

    //$item['content'] = str_replace('</br>', '', $item['content']);

    $item['content'] = preg_replace('/(\b)(email|web|presenter|producer|links): /i', '$1<br>$2: ', $item['content']);
    $item['content'] = preg_replace_callback('!https?://\S+!', array($this, 'make_link'), $item['content']);
    $item['content'] = preg_replace_callback('/ www\.\S+/', array($this, 'make_link_www'), $item['content']);
    $item['content'] = str_replace('href=www', 'href=http://www', $item['content']);
    include $this->dir . '/programme.php';
  }

  function load_files(){
    $files = glob($this->dir . '/audio/*.mp3');
    sort($files); // sort by name = date descending
    array_pop($files); // remove last file, currently recording; make this dependent on a $_GET parameter?

    $dates = array();
    foreach ($files as $file)
      $dates[] = $this->date_from_filename($file);
    $n = count($dates);

    $programmes = $this->load_calendar();
    $count = count($programmes);

    $future = array();
    for ($i = 0; $i < $count; $i++){
      $item = &$programmes[$i];
      $start = $item['start'];
      $end = $item['end'];

      for ($j = 0; $j < $n; $j++){
        $date = $dates[$j];
        if ($date >= $start && $date < $end)
          $item['files'][] = array('file' => 'audio/' . basename($files[$j]), 'date' => $date, 'size' => filesize($files[$j]));
        if ($date >= $end)
          break;
      }

      if ($start > $this->now){
        if (!isset($last_item))
          $last_item = current(array_splice($programmes, $i - 1, 1));

        $future[] = $item;
      }
    }

    $last_item['files'][] = array('file' => $this->stream, 'title' => 'Live stream');
    array_unshift($future, $last_item);
    array_pop($future);

    return array($programmes, $future);
  }
}

