#!/bin/sh

cachefile='/tmp/lm_sensors.log' 
if [ -f $cachefile ]; then
    cachefileage=$(($(date +%s) - $(stat -c %Y $cachefile)))
    if [ $cachefileage -gt 300 ]; then
        sensors > $cachefile
    fi
else
    sensors > $cachefile
fi

cat $cachefile | grep "$1" | cut -d ":" -f 2 | sed -e 's/^[ \t]*//' | cut -d " " -f 1 | tr -d "+°C?"