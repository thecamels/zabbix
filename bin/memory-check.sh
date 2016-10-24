#!/bin/bash
# ============================================================
#  Author: chusiang / chusiang (at) drx.tw
#  Blog: http://note.drx.tw
#  Filename: memory-check.sh
#  Modified: 2016-10-24 13:05
#  Description: Monitoring memory usage of specific process.
#  Reference: 
#
#   1. Total memory used by Python process? | Stack Overflow
#    - http://stackoverflow.com/a/40173829/686105
#
# =========================================================== 

PROCESS_NAME="$1"
ps aux | grep $PROCESS_NAME | awk '{ sum=sum+$6 }; END { print sum }'
