#! /bin/bash
# -*- bash -*-

: ${CCSDLIB:=/etc/ccsd.sh}



if [ ! -e  ${CCSDLIB} ] ; then
    echo "Don't find ccsd.sh, please set CCSDLIB variable to find it"
    exit 1;
fi

. ${CCSDLIB}

usage() {
    echo "loadVirtuoso -e <environnement> -p <password for dba> -t <MMDD> [-Crn]"
    echo -e "\t-r (Recompute Rdf Files)"
    echo -e "\t-C (Don't clean rdf directory)"
    echo -e "\t-t <Date for cleaning>: rdf files before this date will be deleted"
    echo -e "\t-n No Action performed"
    echo -e "\t-D No drop cache performed"
    echo -e "\t-L No load  in virtuoso is performed"
    echo
    echo -e "\t-H All job for rdf generation"
    echo -e "\t-V All job for loading in virtuoso"
    echo
    echo -e "\t-d Debug"
    echo -e "\t-v Verbose"
    exit 1;
}


VIRTUOSOSTATUSFILE=/cache/hal/production/rdf/virtuoso-production-host
LOGFILE=/cache/hal/production/rdf/toggle.log
ISQL=/usr/local/bin/isql
WC=/usr/bin/wc

action=''
verb=0
debug=0
debugopt=''
verbopt=''
cachedir=/cache
pwd=NoPasswd
doClean=1
doDropCache=1
doRdf=0
doLoad=1
date=''
redirect='>'

while getopts ":e:t:p:CLvnrDHVd" option; do
    case "${option}" in
        e)
            env=${OPTARG}
            case $env in
		       production);;
		       preprod);;
		       development);;
		       testing);;
		       *)
		           echo "Env must have value in [ production preprod development testing]"
		           usage;;
	         esac
            ;;
        t)
            date=${OPTARG}
	        case X$date in
		        X????);;
		        *) echo "Date format must be MMDD"; usage;;
	         esac
	         # on retient que l'argument a ete donne...
	         optdate=1
            ;;
	    p)
	        pwd=${OPTARG}
	    ;;
	    D)  doDropCache=0
	    ;;
	    L)  doLoad=0
	    ;;	    
	    C)  doClean=0
	    ;;
	    v)  verb=1
	        verbopt=-v
	    ;;
	    d)  debug=1
	        debugopt=-d
	    ;;
	    H)  doLoad=0
	        doDropCache=0
	        doRdf=1
	        doClean=1
	        date=`date +"%m%d"`
	    ;;
	    V)  doClean=0
	        doRdf=0
	    ;;
	    r)  doRdf=1
	        date=`date +"%m%d"`
		    ;;
	    n)  action=echo
	        redirect=' vers '
	    ;;
        *)
            usage
            ;;
    esac
done
shift $((OPTIND-1))
if [ -z "${env}" ]; then
    echo >&2 "You must specify environment (-e option)"
    usage
fi
if [ -z "${date}" ]  && [ "${doClean}" = 1 ]; then
    echo >&2 "If Clean must be done, you need to specify a date with -t option"
    usage
fi
if [ -n "${optdate}" ]  && [ "${doRdf}" = 1 ]; then
    echo >&2 "If option -r, date is calculated, don't  give it"
    usage
fi
cd $cachedir/hal/$env/rdf

# control du repertoire...
[ -e author ] || { echo "Bad dir"; exit 1; }

begin=`date`
case $doRdf in
    0);;
    1)
	if [ -e /cache/hal ] && [ -e /data/hal ] && [ -e /docs/00 ] ; then
	    : Ok
	else
	    echo "Rdf file must be done on the proper machine... ex ccsdcron"
	    exit 1;
	fi
	case `whoami` in
	    nobody) verbose "-- Recompute all RDF files"
		    for graph in author idhal typdoc structure subject anrproject europeanproject revue document ; do
			verbose "Do graph $graph ..."
			$action php /sites/HalInstances/hal/$env/scripts/rdf.php --graph $graph -e $env  $verbopt $debugopt &
		    done
		    ;;
	    *) echo "Must be nobody to Recompute RDF files"
	       ;;
	esac
	;;
esac
wait
verbose "--- Done."

productionHost=`cat $VIRTUOSOSTATUSFILE 2>/dev/null`
hostname=`/bin/hostname`

if  [ X$hostname = X$productionHost ] ; then
     : "Deja machine de production"
     : "On ne reconstruit surtout pas la base! elle est utilisee"
     exit 0
else
     : "Mise a jour de virtuoso"
     : "Plusieurs jours de travail..."
fi

case $doClean in
    0) ;;
    1)
	verbose "---  Clean dirs"
	/usr/bin/touch -t ${date}0001 /tmp/stamp    # [[CC]YY]MMDDhhmm[.ss]
	for i in  anrProject  author  doctype  document  europeanProject  revue  structure  subject ; do
	    echo $i;
	    [ -d $i ] && /usr/bin/find $i -type f -not -newer /tmp/stamp | $action xargs -n 40 rm -f ;
	done
	verbose "--- Done."
	;;
esac

# temps en ms
#   67978 europeanProject
#    1400 anrProject
# 1205855 author
# ...
case $doDropCache in
    0) ;;
    1) 
	verbose "--- Drop Virtuoso graph"
	for graph in \
	    https://data.archives-ouvertes.fr/subject/          \
		https://data.archives-ouvertes.fr/doctype/          \
		https://data.archives-ouvertes.fr/revue/            \
		https://data.archives-ouvertes.fr/anrProject/       \
		https://data.archives-ouvertes.fr/europeanProject/  \
		https://data.archives-ouvertes.fr/structure/        \
		https://data.archives-ouvertes.fr/author/           \
		https://data.archives-ouvertes.fr/document/         \
	    ; do
	    verbose "Drop graph: $graph"
	    $action $ISQL -P $pwd  "EXEC=sparql drop  silent graph <$graph>"
	    $action $ISQL -P $pwd  "EXEC=sparql create       graph <$graph>"
	done
	verbose "--- Done."
    verbose "Delete old DB.DBA.load_list entries"
 	$action $ISQL -P $pwd  "EXEC=delete from DB.DBA.load_list;"
	;;
esac

# temps de load
# europeanProject: 6773003 msec
#
#
#

case $doLoad in
    0) ;;
    1)
    $action $ISQL -P $pwd  exec="delete from DB.DBA.LOAD_LIST";     # temps exec ~ 68318

	verbose "--- Prerecord load items: environ 2h"
	#  212 027 anrProject
	#   65 323 europeanProject
	#  926 981 structure
	#  879 696 author
	# 4611 805 document
	# ~ 2h
	for graphItem in subject doctype  revue anrProject europeanProject structure author document ; do
	    verbose "    Do $graphItem"
	    $action $ISQL -P $pwd  exec="ld_dir_all('/cache/hal/production/rdf/$graphItem/', '*.rdf', 'https://data.archives-ouvertes.fr/$graphItem/');"
	done
	verbose "--- Done."
	
	verbose "--- Effectively load items: can be very long...)"
	verbose `date`
	# http://vos.openlinksw.com/owiki/wiki/VOS/VirtBulkRDFLoader
	# Section: Running multiple Loaders
	# 55465586 msec ~ 15 h
	$action $ISQL -P $pwd  exec="rdf_loader_run();" &
	# Deadlock in threads....
	#$action $ISQL -P $pwd  exec="rdf_loader_run();" &
	#$action $ISQL -P $pwd  exec="rdf_loader_run();" &
	wait
	$action $ISQL  -P $pwd  exec="checkpoint;"
	verbose "--- Done."
	;;
esac

# Verification du fonctionnement
countGraphs=`$ISQL  -P $pwd  exec="sparql select distinct ?g count(*)  where  { graph ?g {  ?s ?p ?v  } } ;"`

hasDoc=`echo "$countGraphs" | /bin/grep /document/  | /bin/grep -P '\d{8}' | $WC -l`
hasAut=`echo "$countGraphs" | /bin/grep /author/    | /bin/grep -P '\d{8}' | $WC -l`
hasStr=`echo "$countGraphs" | /bin/grep /structure/ | /bin/grep -P '\d{7}' | $WC -l`

case $hasDoc$hasAut$hasStr in
   111)
   eval "$action /bin/hostname $redirect $VIRTUOSOSTATUSFILE"

   $action /sbin/iptables -i eth0 -D INPUT -m tcp -p tcp --dport 8890 -j REJECT
   date=`date`
   $action echo "$date: Start $hostname"  $redirect  $LOGFILE
   ;;
   *)
   # Il manque qq chose, le remplissage de Virtuoso ne n'est pas passe correctement
   echo 2>&1 "---------------------------"
   echo 2>&1 "Update de Virtuoso en echec"
   echo 2>&1 "---------------------------"
   ;;
esac

verbose
verbose "Debut de script: $begin"
end=`date`
verbose "Fin de script  : $end"


