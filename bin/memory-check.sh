#!/bin/bash
# ============================================================
#  Author: chusiang / chusiang (at) drx.tw
#  Blog: http://note.drx.tw
#  Filename: memory-check.sh
#  Modified: 2016-10-24 13:05
#  Description: Monitoring memory usage of specific process.
#
#   The RSS (resident set size) is mean memory used in KB, not B,
#   so we need to `* 1024` for mapping zabbix-server.
#
#  Reference: 
#
#   1. Total memory used by Python process? | Stack Overflow
#    - http://stackoverflow.com/a/40173829/686105
#   2. linux - ps aux output meaning | Super User
#    - http://superuser.com/a/117921/205255
#
# =========================================================== 

PROCESS_NAME="$1"
ps aux | grep $PROCESS_NAME | awk '{ sum=sum+$6 }; END { print sum*1024 }'
