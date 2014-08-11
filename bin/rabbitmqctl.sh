#!/bin/sh

# =======================
cachefile='/tmp/rabbitmqctl.log'
command='sudo /usr/sbin/rabbitmqctl status'
# =======================

random=$RANDOM

if [ ! -e $cachefile ]; then
        touch -d "2 hours ago" $cachefile
fi

cachefileage=$(($(date +%s) - $(stat -c %Y $cachefile)))
process_running=$(ps aux | grep "rabbitmqctl status" | grep -v "grep" | wc -l)

if [ "$cachefileage" -gt 60 ] && [ "$process_running" -eq 0 ]; then
    output=$($command 2>&1)

        if [ $? -eq 0 ]; then
                echo "$output" > $cachefile.$random
                mv $cachefile.$random $cachefile
                chown zabbix.zabbix $cachefile
        fi
fi

cat $cachefile