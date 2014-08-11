#!/bin/sh

# =======================
cachefile='/tmp/rabbitmq.log'
command='sudo /usr/bin/php /etc/zabbix/bin/rabbit.php'
# =======================

random=$RANDOM

if [ ! -e $cachefile ]; then
        touch -d "2 hours ago" $cachefile
fi

cachefileage=$(($(date +%s) - $(stat -c %Y $cachefile)))
process_running=$(ps aux | grep rabbit.php | grep -v "grep" | wc -l)

if [ "$cachefileage" -gt 60 ] && [ "$process_running" -eq 0 ]; then
    output=$($command 2>&1)

        if [ $? -eq 0 ]; then
                echo "$output" > $cachefile.$random
                mv $cachefile.$random $cachefile
                chown zabbix.zabbix $cachefile
        fi
fi

cat $cachefile