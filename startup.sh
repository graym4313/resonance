#!/bin/bash

cd /home/alfeaton/resonance.macropus.org

# delete files older than 7 days
find audio -type f -mtime +7 -exec rm '{}' \;

kill `cat pid`

rm 'feed.xml'

/usr/local/bin/php-5.3 record.php &

echo $! > pid

