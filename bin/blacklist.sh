#!/bin/bash 
# http://giantdorks.org/alain/shell-script-to-query-a-list-of-hostnames-or-ip-addresses-against-a-list-of-local-and-remote-dns-black-lists/
# Modified by Gerard Stanczak gerard@thecamels.org 
# Usage: blacklist.sh DOMAIN  
 
# IPs or hostnames to check if none provided as arguments to the script
hosts='
'
 
# Locally maintained list of DNSBLs to check
LocalList='
b.barracudacentral.org
'
 
# pipe delimited exclude list for remote lists
Exclude='spamtrap.drbl.drand.net|dnsbl.proxybl.org|^dnsbl.mailer.mobi$|^foo.bar$|^bar.baz$|^.*webiron.*$'
 
# Remotely maintained list of DNSBLs to check
WPurl="https://en.wikipedia.org/wiki/Comparison_of_DNS_blacklists"
WPlst=$(curl -s $WPurl | egrep "<td>([a-z]+\.){1,7}[a-z]+</td>" | sed -r "s|</?td>||g;/$Exclude/d")
 
 
# ---------------------------------------------------------------------
 
HostToIP()
{
 if ( echo "$host" | egrep -q "[a-zA-Z]" ); then
   IP=$(host "$host" | awk '/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/ {print$NF}')
 else
   IP="$host"
 fi
}
 
Reverse()
{
 echo $1 | awk -F. '{print$4"."$3"."$2"."$1}'
}
 
Check()
{
 result=$(dig +short $rIP.$BL)
 if [ -n "$result" ]; then
   echo -e "$host  LISTED  $BL (answer = $result)"
 else
   echo -e "$host  OK  $BL"
 fi
}
 
if [ -n "$1" ]; then
  hosts=$@
fi
 
if [ -z "$hosts" ]; then
  hosts=$(netstat -tn | awk '$4 ~ /[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/ && $4 !~ /127.0.0/ {gsub(/:[0-9]+/,"",$4);} END{print$4}')
fi
 
for host in $hosts; do
  HostToIP
  rIP=$(Reverse $IP)
  # Checking $IP against BLs from $WPurl"
  for BL in $WPlst; do
    Check
  done
  # Checking $IP against BLs from a local list"
  for BL in $LocalList; do
    Check
  done
done