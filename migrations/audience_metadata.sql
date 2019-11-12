#! /bin/bash

env=$1

[ -e /etc/ccsd/shlibcnf ] && . /etc/ccsd/shlibcnf
[ "X$ccsdShlibLoaded" = "X" ] && [ -e /sites/shlib/ccsd.sh ] && . /sites/shlib/ccsd.sh 
[ "X$ccsdShlibLoaded" = "X" ] && { echo "Can't load Ccsd SH library"; exit 1; }
setHalDB   $env || exit 1
setIndexDB $env || exit 1

mysqlcmd="mysql --skip-column-names -h $haldbhost -P $haldbport $haldb"
mysqlcmdIndex="mysql --skip-column-names  -h $haldbhost -P $indexdbport $indexdb"

debug=1
verbose=1

setPassword
# -- requetes SQL pour supprimer les metadata audience = 0
sqlquery="SELECT DOCID FROM DOC_METADATA WHERE METANAME LIKE 'audience' AND METAVALUE LIKE '0';"
readMysql "$sqlquery" > /tmp/listDocid$$

sqlquery="UPDATE DOC_METADATA SET METAVALUE = 1 WHERE METANAME = 'audience' AND METAVALUE = 0;"
toMysql "$sqlquery"


echo > /tmp/cmdsql
for i in `cat /tmp/listDocid$$` ; do
    echo "INSERT INTO INDEX_QUEUE (ID, DOCID, UPDATED, APPLICATION, ORIGIN, CORE, PRIORITY, STATUS) VALUES (NULL, $i, CURRENT_TIMESTAMP, 'Modif audience meta', 'UPDATE', 'hal', '0', 'ok');" >> /tmp/cmdsql
done
# cat /tmp/cmdsql | $mysqlcmdIndex
