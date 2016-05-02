#!/bin/bash
# If you want to monitor "foo" and "bar" databases, you set the GETDB as
# GETDB="select datname from pg_database where datname in ('foo','bar');"
# CREATE USER zabbix WITH PASSWORD 'asfrtgrg432f';
# GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO zabbix;

GETDB="select datname from pg_database where datistemplate = 'f';"

for dbname in $(psql -U zabbix -d postgres -t -c "${GETDB}"); do
    dblist="$dblist,"'{"{#DBNAME}":"'$dbname'"}'
done
echo '{"data":['${dblist#,}' ]}'