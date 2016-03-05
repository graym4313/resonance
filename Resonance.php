<?php

date_default_timezone_set('Europe/London');

require __DIR__ . '/config.php';

/**
 * Handle recording the stream to files and outputting HTML
 */
class Resonance
{
    public $now;
    public $oldDate;
    private $calendar = 'calendar.json';
    //public $stream = 'http://icecast.commedia.org.uk:8000/resonance_hi.mp3';
    public $stream = 'http://54.77.136.103:8000/resonance';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->now = time();
    }

    /**
     * @param null $date
     */
    public function start_recording($date = null)
    {
        if (!$date) {
            $date = date('Y-m-d_Hi', $this->now);
        }

        $file = sprintf(__DIR__ . '/audio/ResonanceFM_%s~.mp3', $date);
        $f = fopen($file, 'w');

        $ch = curl_init($this->stream);

        curl_setopt_array(
            $ch,
            array(
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_FILE => $f,
            )
        );

        curl_exec($ch);
        fclose($f);
    }

    /**
     * Write tags to MP3 files
     */
    public function tag_files()
    {
        $files = glob('audio/*~.mp3');
        if (empty($files)) {
            return;
        }

        require_once(__DIR__ . '/getid3/getid3.php');
        $getID3 = new getID3;
        $getID3->setOption(array('encoding' => 'UTF-8'));
        require_once(__DIR__ . '/getid3/write.php');

        $programmes = $this->load_calendar();

        foreach ($files as $file) {
            //print "tagging $file\n";

            $date = $this->date_from_filename($file);

            $title = 0;
            $item = null;
            foreach ($programmes as $item) {
                if ($date >= $item['start'] && $date < $item['end']) {
                    $title = $item['title'];
                    break;
                }
            }

            if (!$title) {
                $title = 'Resonance104.4fm';
            }

            $show = str_replace(' (repeat)', '', $title);

            $episode = $show . ' ' . date('Y-m-d H:i', $date); // . '-' . date('H:i', $date + 60*30);

            //print "$show\n$episode\n";

            $outputFile = str_replace('~.mp3', '.mp3', $file);

            $tagwriter = new getid3_writetags;
            $tagwriter->filename = $file;
            $tagwriter->tagformats = array('id3v1', 'id3v2.3');
            $tagwriter->overwrite_tags = true;
            $tagwriter->tag_encoding = 'UTF-8';

            $tagwriter->tag_data = array(
                'artist' => array('Resonance104.4fm'),
                'album' => array($show),
                'title' => array($episode),
                'year' => array(date('Y', $date)),
                'comment' => array($item['content']),
            );

            if ($tagwriter->WriteTags()) {
                rename($file, $outputFile);
            } else {
                print implode("\n", $tagwriter->errors) . implode("\n", $tagwriter->warnings);
            }

            $dir = __DIR__ . '/audio/shows/' . preg_replace('/[^\w\(\)\_\- ]/', '_', $title);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            symlink('../../' . basename($outputFile), $dir . '/' . basename($outputFile));
        }
    }

    /**
     * @return array
     */
    public function load_calendar()
    {
        $day = 60 * 60 * 24;

        $params = array(
            'maxResults' => 1000,
            'orderBy' => 'startTime',
            'singleEvents' => 'true',
            'timeMin' => date(DATE_ATOM, $this->now - $day),
            'timeMax' => date(DATE_ATOM, $this->now + $day * 2),
            'timeZone' => 'Europe/London',
            'key' => GOOGLE_API_KEY,
        );

        $url = 'https://www.googleapis.com/calendar/v3/calendars/schedule%40resonancefm.com/events?' . http_build_query($params);

        if (!file_exists($this->calendar)) {
            file_put_contents($this->calendar, file_get_contents($url));
        }

        $calendar = json_decode(file_get_contents($this->calendar), true);

        $items = array();

        foreach ($calendar['items'] as $event) {
            if (isset($event['description'])) {
                $content = trim($event['description']);
                $content = str_replace('<BR>', '', $content);
                $content = preg_replace('/\n+/', "\n", $content);
                $content = preg_replace('/(email|web|presenter|producer|links): /i', "\n" . '$1: ', $content);
            } else {
                $content = '';
            }

            $items[] = array(
                'title' => $event['summary'],
                'content' => $content,
                'start' => strtotime($event['start']['dateTime']),
                'end' => strtotime($event['end']['dateTime']),
            );
        }

        return $items;
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    public function make_link($matches)
    {
        $url = $matches[0];

        return sprintf('<a href="%s">%s</a>', htmlspecialchars($url), htmlspecialchars($url));
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    public function make_link_www($matches)
    {
        $url = trim($matches[0]);

        return sprintf(' <a href="%s">%s</a>', htmlspecialchars('http://' . $url), htmlspecialchars($url));
    }

    /**
     * @param float $bytes
     *
     * @return string
     */
    public function format_file_size($bytes)
    {
        $text = array('bytes', 'kb', 'MB', 'GB', 'TB', 'PB');
        $item = (int) floor(log($bytes) / log(1024));

        return sprintf('%d ' . $text[$item], ($bytes / pow(1024, floor($item))));
    }

    /**
     * @param string $file
     *
     * @return int
     */
    public function date_from_filename($file)
    {
        preg_match('/(\d{4}-\d{2}-\d{2})_(\d{2})(\d{2})/', basename($file), $matches);
        list(, $date, $hour, $min) = $matches;

        return strtotime(sprintf('%sT%d:%d', $date, $hour, $min));
    }

    /**
     * @param array $item   Programme
     * @param bool  $future Whether this item is in the future
     */
    public function print_programme($item, $future = false)
    {
        if (!$future && empty($item['files'])) {
            return;
        }

        $start = $item['start'] - 60 * 60 * 2; // day ends at 2am
        $date = date('Y-m-d', $start);
        if ($date != $this->oldDate) {
            if ($this->oldDate && !$future) {
                print '</td><td class="listing">';
            }
            printf('<h2>%s</h2>', date('l, j F', $start));
        }
        $this->oldDate = $date;

        //$item['content'] = str_replace('</br>', '', $item['content']);

        $item['content'] = preg_replace(
            '/(\b)(email|web|presenter|producer|links): /i',
            '$1<br>$2: ',
            $item['content']
        );
        //$item['content'] = preg_replace_callback('!https?://\S+!', array($this, 'make_link'), $item['content']);
        $item['content'] = preg_replace_callback('/ www\.\S+/', array($this, 'make_link_www'), $item['content']);
        $item['content'] = str_replace('href=www', 'href=http://www', $item['content']);
        include __DIR__ . '/programme.php';
    }

    /**
     * @return array
     */
    public function load_files()
    {
        $files = glob(__DIR__ . '/audio/*.mp3');
        sort($files); // sort by name = date descending
        array_pop($files); // remove last file, currently recording; make this dependent on a $_GET parameter?

        $dates = array();
        foreach ($files as $file) {
            $dates[] = $this->date_from_filename($file);
        }
        $n = count($dates);

        $programmes = $this->load_calendar();
        $count = count($programmes);

        $future = array();
        for ($i = 0; $i < $count; $i++) {
            $item = &$programmes[$i];
            $start = $item['start'];
            $end = $item['end'];

            for ($j = 0; $j < $n; $j++) {
                $date = $dates[$j];
                if ($date >= $start && $date < $end) {
                    $item['files'][] = array(
                        'file' => 'audio/' . basename($files[$j]),
                        'date' => $date,
                        'size' => filesize($files[$j])
                    );
                }
                if ($date >= $end) {
                    break;
                }
            }

            if ($start > $this->now) {
                if (!isset($lastItem)) {
                    $lastItem = current(array_splice($programmes, $i - 1, 1));
                }

                $future[] = $item;
            }
        }

        $lastItem['files'][] = array('file' => $this->stream, 'title' => 'Live stream');
        array_unshift($future, $lastItem);
        array_pop($future);

        return array($programmes, $future);
    }
}

