#! /bin/sh

corelist="ref_author ref_domain  ref_journal ref_metadatalist ref_projanr ref_projeurop ref_structure hal"
dir=`dirname $0`
usage="$0 -v(verbose) -d(debug) -t(test) <environnement> <UPDATE|DELETE>";

verbose=0
test=0
debug=0
verbose() {
    [ $verbose -eq 1 ] && echo $*
}
debug() {
    [ $debug -eq 1 ] && echo $*
}
verboseOpt=''
debugOpt=''
testOpt=''
# Prefix pour les nom de core suivant l'instance
instance=''  # application hal standard par default (pas de prefix aux core)
instanceSep=''  # instance avec separateur
while getopts "tvdhi:" opt; do
    case $opt in
        v) verbose=1; verboseOpt=-v
           ;;
        d) debug=1;debugOpt=-d
           ;;
        t) test=1;testOpt=-t
           ;;
        i) instance="$OPTARG"
           instanceSep="$instance-"
           INSTANCE=$instance
           export INSTANCE
           ;;
	    h) echo $usage
	   exit 0
	   ;;
    esac
done
shift `expr $OPTIND - 1`
case $# in
    2) : ok
       ;;
    *) echo "Need 2 args"
       echo $usage
       exit 1;
       ;;
esac

case $1 in
    production|preprod|test|development) : ok;;
    *) echo "Arg1 must be an environment name: production|preprod|test|development"
       exit 1;;
esac


for core in $corelist ; do
    # On utilise le prefix de l'instance pour le nom des core
    # exemple: halspm => donne halspm-ref_author
    core=$instancesep$core
    verbose "-----------------------------------"
    verbose "Do $2 in environment $1 for $core"
    case $test in
	1)
	    # No & in test case
	    $dir/library/solr/indexer/php_launch_command.sh  $verboseOpt $debugOpt $testOpt $dir/solrJob.php -e $1 --cron $2 -c $core 
	    ;;
	0)
	    $dir/library/solr/indexer/php_launch_command.sh  $verboseOpt $debugOpt $testOpt $dir/solrJob.php -e $1 --cron $2 -c $core &
	    ;;
    esac
done 
