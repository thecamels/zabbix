#!bin/bash

comma=1
echo -n "{\"data\":["

for i in $(sudo /bin/find /var/lib/mysql -type f -printf %p+%s= | tr "=" "\n" | grep -v "\/mysql\/mysql\/" | grep "\.ibd" | cut -d "+" -f 1 | sed 's/@002d/\-/g' | cut -d "/" -f 5- | sed 's/.ibd//g' ); do

           dbname=$(echo $i | cut -d '/' -f 1)
           tblname=$(echo $i | cut -d '/' -f 2)

           if [ $comma -eq 0 ]; then
                echo -n ","
           fi
           comma=0
           echo -n "{\"{#DBNAME}\":\"$dbname\",\"{#TABLENAME}\":\"$tblname\"}"

done

echo -n "]}"
