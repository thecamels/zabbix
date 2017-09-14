Zabbix
======
Script and templates for Zabbix 2.2.x, 2.4.x, 3.0.x, 3.2.x

- In bin you will find Bash/Perl/PHP scripts used by some User Parameters (need to be installed on agent)
- In sudoers.d you can find settings for sudo
- In Templates there are XML files ready to import using Zabbix GUI
- In zabbix_agentd.conf.d there are custom UserParameter (need to be installed on agent)

Templates was tested on Red Hat 5.x, 6.x and CentOS 5.x, 6.x. Common UserParameter were added to ```zabbix_agentd.conf.d/linux.conf``` file. Please add it to your own Zabbix Agent installation. Sometimes you need to use ```sudo``` for ```UserParameter```. All rules are in file ```sudoers.d/zabbix```.

Please let us know if you have any questions or concerns.

[The Camels Team](http://thecamels.org)

# Table of contents
-----
1. Templates
   * [Amavisd](#template-app-amavisd)
   * [Apache](#template-app-apache)
   * [APC](#template-app-apc)
   * [BIND](#template-app-bind)
   * [Brocade HBA](#template-app-brocade-hba)
   * [ClamAV](#template-app-clamav)
   * [CPanel](#template-app-cpanel)
   * [Dovecot](#template-app-dovecot)
   * [Etherpad](#template-app-etherpad)
   * [EXIM](#template-app-exim)
   * [Fail2Ban](#template-app-fail2ban)
   * [Iptables](#template-app-iptables)
   * [lm_sensors](#template-app-lm_sensors)
   * [mdadm](#template-app-mdadm)
   * [Memory Usage of Specific Process](#template-specific-process-memory-usage)
   * [Monit](#template-app-monit)
   * [MySQL](#template-app-mysqld)
   * [MySQL Slave](#template-app-mysql-slave)
   * [Nginx](#template-app-nginx)
   * [Nscd](#template-app-nscd)
   * [OpCahce](#template-app-opcache)
   * [OpenDKIM](#template-app-opendkim)
   * [OpenVPN](#template-app-openvpn)
   * [Percona Xtrabackup](#template-app-percona-xtrabackup)
   * [PHP](#template-app-php)
   * [PHP FPM](#template-app-php-fpm)
   * [Postfix](#template-app-postfix)
   * [PostgreSQL](#template-app-postgresql)
   * [PowerPath](#template-app-powerpath)
   * [Pure-FTPd](#template-app-pure-ftpd)
   * [RabbitMQ](#template-app-rabbitmq)
   * [Redis](#template-app-redis)
   * [Rserve](#template-app-rserve)
   * [Squid](#template-app-squid)
   * [S.M.A.R.T.](#template-app-s.m.a.r.t.)
   * [Solr](#template-app-solr)
   * [Spamassassin](#template-app-spamassassin)
   * [SPAM Blacklist](#template-app-spam-blacklist)
   * [Unicorn](#template-app-unicorn)
   * [Unicorn Rails](#template-app-unicorn-rails)
   * [vsftpd](#template-app-vsftpd)
   * [Back-UPS ES-700g](#template-device-back-ups-es-700g)
   * [BigIP F5](#template-device-bigip-f5)
   * [Cisco ASA](#template-device-cisco-asa)
   * [NetApp](#template-device-netapp)
   * [SNMP generic](#template-device-snmp-generic)
   * [Inventory](#template-inventory)
   * [Dell OMSA](#template-omsa)
   * [SELinux](#template-selinux)
   * [Wowza](#template-wowza)

-----

## Template App Amavisd
Monitoring for Amavisd service.

## Template App Apache
Monitoring for Apache service.

## Template App APC
Monitoring memory usage of APC (http://pecl.php.net/package/APC) module. File ```bin/apc-stats.php``` need to be accessed via HTTP for example http://127.0.0.1/apc-stats.php

## Template App BIND
Monitoring for BIND (DNS server) service. Checking also version of BIND.

## Template App Brocade HBA
Monitoring for Network Adapters - Brocade. Template is using Discovery to create Items and Triggers. You need also add SUDO for zabbix user: ```zabbix ALL=(ALL) NOPASSWD: /usr/bin/bcu```

## Template App ClamAV
Monitoring for ClamAV.

## Template App cPanel
Monitoring basic WHM/cPanel services.

## Template App Dovecot
Monitoring for Dovecot.

## Template App Etherpad
Monitoring for Etherpad (http://etherpad.org/)

## Template App Exim
Monitoring for mail server Exim.

## Template App Fail2Ban
Monitoring for Fail2Ban, an intrusion prevention software framework that protects computer servers from brute-force attacks.

## Template App Iptables
Monitoring for Iptables. You need also add SUDO for zabbix user: ```zabbix  ALL=(ALL) NOPASSWD: /sbin/iptables -L INPUT -n```

## Template App lm_sensors
Monitoring CPU and MotherBoard temperatures by lm_senros module.

## Template App mdadm
Monitoring mdadm arrays. Checking number of corrupted disk arrays.

## Template Specific Process Memory Usage
Monitoring memory usag of Specific Process, like Python, unicorn-rails and Nginx.

## Template App Monit
Monitoring for Monit service.

## Template App MySQLd
Monitoring for MySQL 5.5, 5.6 and 5.7. It is using PHP-cli for monitoring. You need also add zabbix user to database. Please run SQL query:
```
CREATE USER 'zabbix'@'localhost' IDENTIFIED BY 'ha7jqnlacwefrs';
GRANT REPLICATION CLIENT, SELECT, PROCESS, SHOW DATABASES ON *.* TO 'zabbix'@'localhost' IDENTIFIED BY 'ha7jqnlacwefrs';
FLUSH PRIVILEGES;
```

If you want change password, you need to edit files ```.my.cnf``` and also in file ```bin/ss_get_mysql_stats.php```.

## Template App MySQL Slave
Monitoring for replication in MySQL 5.5, 5.6 and 5.7. Please run SQL query:
```
CREATE USER 'zabbix'@'localhost' IDENTIFIED BY 'ha7jqnlacwefrs';
GRANT REPLICATION CLIENT, SELECT, PROCESS, SHOW DATABASES ON *.* TO 'zabbix'@'localhost' IDENTIFIED BY 'ha7jqnlacwefrs';
FLUSH PRIVILEGES;
```

If you want change password, you need to edit files ```.my.cnf```.

## Template App Nginx
Monitoring for Nginx. It is using script ```nginx-check.sh``` written by Vincent Viallet.

## Template App Nscd
Nscd is a daemon that provides a cache for the most common name service requests. Nscd provides caching for accesses of the passwd(5), group(5), and hosts(5) databases through standard libc interfaces, such as getpwnam(3), getpwuid(3), getgrnam(3), getgrgid(3), gethostbyname(3), and others.

You need also add SUDO for zabbix user: ```zabbix  ALL=(ALL) NOPASSWD: /usr/sbin/nscd -g```

## Template App OPcache
Monitoring memory usage of OPcache (http://php.net/manual/en/book.opcache.php). File ```bin/opcache.php``` need to be accessed via HTTP for example http://127.0.0.1/opcache.php. Curl need to be installed on server.

## Template App OpenDKIM
Monitoring for OpenDKIM.

## Template App OpenVPN
Monitoring for OpenVPN service.

## Template App Percona XtraBackup
Monitoring for Percona XtraBackup.

## Template App PHP
Monitoring for PHP.

## Template App php-fpm
Monitoring for php-fpm.

## Template App Postfix
Monitoring for mail server Postfix.

## Template App PostgreSQL
Monitoring for PostgreSQL.

## Template App PowerPath
Monitoring for EMC PowerPath: Host-based software for automated data path management, failover and recovery, and optimized load balancing. PowerPath automates, standardizes, and optimizes data paths in physical and virtual environments as well as cloud deployments to deliver high availability and performance.

## Template App Pure-FTPd
Monitoring for Pure-FTPd.

## Template App RabbitMQ
Monitoring RabbitMQ (http://www.rabbitmq.com/) basic parameters like queues, exchanges and memory usage. You need install PHP on server for monitoring RabbitMQ. You need also add SUDO for zabbix user:

```
zabbix ALL=(ALL) NOPASSWD: /usr/sbin/rabbitmqctl
zabbix ALL=(ALL) NOPASSWD: /usr/bin/php /etc/zabbix/bin/rabbit.php
```

## Template App Redis
Monitoring for redis server 3.x. Support for redis cluster.

## Template App Rserve
Monitoring for Rserve, Binary R server.

## Template App Squid
Monitoring for Squid, a caching proxy for the Web supporting HTTP, HTTPS, FTP, and more.

## Template App S.M.A.R.T.
Monitoring for S.M.A.R.T. enabled storage devices (HDD's, SSD's and other). Uses discovery script to populate disks. INFO: For non present S.M.A.R.T. values disable items on per-host level.

## Template and scripts created by:
Michał Macioszek, Taras Baran, Michal Gębora, Marcin Wilk, Maks Bednarek, Anna Fałek, Mikołaj Szczuraszek

## Template App Solr
Monitoring for Solr.

## Template App Spamassassin
Monitoring for Spamassassin.

## Template App SPAM Blacklist
Monitoring for SPAM Blacklist.

## Template App Unicorn
Monitoring for Unicorn.

## Template App Unicorn Rails
Monitoring for Unicorn Rails.

## Template App vsftpd
Monitoring for vsftpd.

## Template Device Back-UPS ES 700G
Monitoring of APC UPS dedicated battery power. Requires package apcupsd to be installed.

## Template Device BigIP F5
Monitoring of F5 BigIP network load balancer. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, hardware failure, global traffic) and also it discovers network interfaces, storage, virtual servers and pools.

## Template Device Cisco ASA
Monitoring of Cisco ASA firewall. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, hardware failure, global traffic) and also it discovers network interfaces. It requires manual addition of value mappings (Administration -> General -> Value Mapping)

```
Cisco ASA Failover status
9 - Active
10 - Standby

Cisco Interface Status
1 - up
2 - down
```

## Template Device NetApp
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

## Template Device SNMP Generic
This is a generic template for SNMP (and any other in fact) devices that do not provide any information via SNMP/any other protocol. It only checks for host availability

## Template Inventory
This template is used to collect data for automatic host inventory (architecture, operating system, hardware etc). Requires facter (puppetlabs)

## Template OMSA
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

## Template SELinux
Monitoring for SELinux service.

## Template Wowza
Monitoring for Wowza Streaming Engine server
