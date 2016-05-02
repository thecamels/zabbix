#!/bin/bash

swap=$(free -m | grep -i swap | awk '{ print $2 }')

if [ "$swap" != "0" ]; then
   echo '{"data":[{"{#TOTAL}":"total", "{#FREE}":"free", "{#PFREE}":"pfree", "{#PUSED}":"pused"  } ]}'
fi
