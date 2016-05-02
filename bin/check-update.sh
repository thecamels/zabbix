#!/bin/bash
day=$(date +%e)
if ((day <= 7)) ; then
	result=`/usr/bin/yum -q check-update | grep -v "^$"`
	echo -n "$result" > /tmp/yum-update-pending
	chown zabbix.zabbix /tmp/yum-update-pending
fi
