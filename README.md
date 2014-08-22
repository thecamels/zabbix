Zabbix
======
Script and templates for Zabbix 2.2.x. 

- In /bin you will find Bash/Perl/PHP scripts used by some User Parameters (need to be installed on agent)
- In Templates there are XML files ready to import using Zabbix GUI
- In zabbix_agentd.conf.d there are custom User Parameters (need to be installed on agent)

Please let us know if you have any questions or concerns.

The Camels Team
http://thecamels.org

Template App APC
======
Monitoring memory usage of APC (http://pecl.php.net/package/APC) module. File apc-stats.php need to be accessed via HTTP for example http://127.0.0.1/apc-stats.php

Template App OPcache
======
Monitoring memory usage of OPcache (http://php.net/manual/en/book.opcache.php). File opcache.php need to be accessed via HTTP for example http://127.0.0.1/opcache.php. Curl need to be installed on server.

Template App RabbitMQ
======
Monitoring RabbitMQ (http://www.rabbitmq.com/) basic parameters like queues, exchanges and memory usage. You need install PHP on server for monitoring RabbitMQ.

Template App cPanel
======
Monitoring basic WHM/cPanel services.

Template Device BigIP F5
======
Monitoring of F5 BigIP network load balancer. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, hardware failure, global traffic) and also it discovers network interfaces, storage, virtual servers and pools

Template Device Cisco ASA
======
Monitoring of Cisco ASA firewall. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, hardware failure, global traffic) and also it discovers 
network interfaces.

Template Device NetApp
======
Monitoring of F5 BigIP network load balancer. It uses SNMP items to monitor basic device parameters (CPU/RAM usage, disk status, hardware failure, global traffic) and also it discovers snap mirrors volumens (more discovery rules in the future)

Template Device SNMP Generic
======
This is a generic template for SNMP (and any other in fact) devices that do not provide any information via SNMP/any other protocol. It only checks for host availability

Template Inventory
======
This template is used to collect data for automatic host inventory (architecture, operating system, hardware etc). Requires facter (puppetlabs)

Template OMSA
======
Collects data from OpenManage Server Administrator (OMSA) tool for Dell Servers. It monitors hardware components such as chassis, disks, power supplies, fans and other

Template Security
======
Contains items related with basic linux security (iptables status, selinux status) and Fail2ban application