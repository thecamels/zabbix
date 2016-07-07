#!/bin/bash

cachefile='/tmp/mysqldbsize.log'
random=$RANDOM

if [ ! -e $cachefile ]; then
        touch -d "2 hours ago" $cachefile
fi

if [ $# -ne 2 ]; then
  echo "Usage: $0 dbname tablename"
  exit 1
fi

if [ -e /san/mysql-fs/mysql ]; then
  path="/san/mysql-fs/mysql/"
else
  path="/var/lib/mysql/"
fi

cachefileage=$(($(date +%s) - $(stat -c %Y $cachefile)))
process_running=$(ps aux | grep 'find /var/lib/mysql' | grep -v "grep" | wc -l)

if [ "$cachefileage" -gt 60 ] && [ "$process_running" -eq 0 ]; then

  sudo /bin/find $path -type f -printf %p+%s= | tr "=" "\n" | grep -v "\/mysql\/mysql\/" | grep "\.ibd" | sed 's/@002d/\-/g' > $cachefile.$random

        if [ $? -eq 0 ]; then
                mv $cachefile.$random $cachefile
                chown zabbix.zabbix $cachefile
        fi
fi

grep "/$1/" $cachefile | grep "/$2\.ibd" | cut -d "+" -f 2

