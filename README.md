Zabbix
======
Script and templates for Zabbix 2.2.x. and 2.4.x

- In /bin you will find Bash/Perl/PHP scripts used by some User Parameters (need to be installed on agent)
- In Templates there are XML files ready to import using Zabbix GUI
- In zabbix_agentd.conf.d there are custom User Parameters (need to be installed on agent)

Templates was tested on Red Hat 5.x, 6.x and CentOS 5.x, 6.x. Common UserParameter were added to ```zabbix_agentd.conf.d/linux.conf``` file. Please add it to your own Zabbix Agent installation.

Please let us know if you have any questions or concerns.

The Camels Team
http://thecamels.org

Template App APC
======
Monitoring memory usage of APC (http://pecl.php.net/package/APC) module. File ```bin/apc-stats.php``` need to be accessed via HTTP for example http://127.0.0.1/apc-stats.php

Template App Brocade HBA
======
Monitoring for Network Adapters - Brocade. Template is usind Discovery to create Items and Triggers.

Template App Nscd
======
Nscd is a daemon that provides a cache for the most common name service requests. Nscd provides caching for accesses of the passwd(5), group(5), and hosts(5) databases through standard libc interfaces, such as getpwnam(3), getpwuid(3), getgrnam(3), getgrgid(3), gethostbyname(3), and others.

You need also add SUDO for zabbix user: ```zabbix  ALL=(ALL) NOPASSWD: /usr/sbin/nscd -g```

Template App OPcache
======
Monitoring memory usage of OPcache (http://php.net/manual/en/book.opcache.php). File ```bin/opcache.php``` need to be accessed via HTTP for example http://127.0.0.1/opcache.php. Curl need to be installed on server.

Template App RabbitMQ
======
Monitoring RabbitMQ (http://www.rabbitmq.com/) basic parameters like queues, exchanges and memory usage. You need install PHP on server for monitoring RabbitMQ.

Template App cPanel
======
Monitoring basic WHM/cPanel services.

Template Device BigIP F5
======
Monitoring of F5 BigIP network load balancer. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, hardware failure, global traffic) and also it discovers network interfaces, storage, virtual servers and pools. It requires manual addition of value mappings (Administration -> General -> Value Mapping)

```
F5 ltmPoolStatusAvailState	
0 - Pool Error (code
1 - Pool available (code
2 - Pool member(s) are currently not available (code
3 - Pool member(s) are down (code
4 - Pool availability is unknown (code
5 - Pool unlicensed (code
```

Template Device Cisco ASA
======
Monitoring of Cisco ASA firewall. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, hardware failure, global traffic) and also it discovers network interfaces. It requires manual addition of value mappings (Administration -> General -> Value Mapping)

```
Cisco ASA Failover status
9 - Active
10 - Standby

Cisco Interface Status
1 - up
2 - down
```

Template Device NetApp
======
Monitoring of NetApp dedicated NAS disk arrays. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, disk status, hardware failure, global traffic) and also it discovers snap mirrors volumens (more discovery rules in the future). It requires manual addition of value mappings (Administration -> General -> Value Mapping)

```
NetAppcfInterconnectStatus
1 - not present
2 - down
3 - not present
4 - up

NetAppcfPartnerStatus
1 - maybe down
2 - ok
3 - dead

NetAppCfSetting
1 - not configured
2 - enabled
3 - disabled
4 - takeover by partner disabled
5 - dead

NetAppCfState
1 - dead
2 - can takeover
3 - cannot takeover
4 - takeover

NetAppFcpTgtStatus
1 - startup
2 - uninitialized
3 - initializingFW
4 - linkNotConnected
5 - waitingForLinkUp
6 - online
7 - linkDisconnected
8 - resetting
9 - offline
10 - offlinedByUserSystem
11 - unknown

NetAppFsStatus
1 - ok
2 - nearlyFull
3 - full

NetAppGlobalStatus
1 - other
2 - unknown
3 - ok
4 - non critical
5 - critical
6 - non recoverable

NetAppLunOnline
1 - false
2 - true

NetAppNvramBatteryStatus
1 - ok
2 - partially discharged
3 - fully discharged
4 - not present
5 - near end of life
6 - at end of life
7 - unknown
8 - overcharged

NetAppOverTemp
1 - no
2 - yes

NetAppRaidStatus
1 - active
2 - reconstructionInProgress
3 - parityReconstructionInProgress
4 - parityVerificationInProgress
5 - scrubbingInProgress
6 - failed
7 - addingSpare
8 - spare
9 - prefailed
10 - offline

NetAppSpareStatus
1 - spare
2 - addingspare
3 - bypassed
4 - unknown
5 - offline

NetAppVolStatus
1 - unmounted
2 - mounted
3 - frozen
4 - destroying
5 - creating
6 - mounting
7 - unmounting
8 - nofsinfo
9 - replaying
```

Template Device SNMP Generic
======
This is a generic template for SNMP (and any other in fact) devices that do not provide any information via SNMP/any other protocol. It only checks for host availability

Template Inventory
======
This template is used to collect data for automatic host inventory (architecture, operating system, hardware etc). Requires facter (puppetlabs)

Template OMSA
======
Collects data from OpenManage Server Administrator (OMSA) tool for Dell Servers. It monitors hardware components such as chassis, disks, power supplies, fans and other. It requires manual addition of value mappings (Administration -> General -> Value Mapping)

```
Dell Open Manage System Status
1 - Other
2 - Unknown
3 - OK
4 - NonCritical
5 - Critical
6 - NonRecoverable
```

You need also add SUDO for zabbix user: ```zabbix  ALL=(ALL) NOPASSWD: /opt/dell/srvadmin/bin/omreport```

Template Security
======
Contains items related with basic linux security (iptables status, selinux status) and Fail2ban application. You need also add SUDO for zabbix user: ```zabbix  ALL=(ALL) NOPASSWD: /sbin/iptables -L INPUT -n```
