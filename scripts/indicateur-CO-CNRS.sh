#! /bin/sh

# url='https://api.archives-ouvertes.fr/search/hal/?'
url='http://ccsdsolrvip.in2p3.fr:8080/solr/hal/select'

structQuery='structName_t%3A%22*CNRS*%22+OR+structAcronym_t%3A%22*CNRS*%22+OR+structCode_s%3A%22*CNRS*%22+OR%0AstructName_s%3A%22*Centre+National+de+Recherche*%22'
typeFilter='ART%20OR%20COMM%20OR%20COUV%20OR%20OTHER%20OR%20OUV%20OR%20DOUV%20OR%20UNDEFINED%20OR%20REPORT%20OR%20THESE%20OR%20HDR%20OR%20LECTURE'

VersionUnique=
datefield=submittedDateY_i
debug=0

# *&fq=submittedDateY_i:2016&fq=docType_s:&rows=0&facet=true&facet.field=submitType_s

while getopts "dq:VDt:" opt; do
    case $opt in
	V) VersionUnique='and+version_i:1'
	   ;;
	D) datefield=producedDateY
	   ;;
	q) structQuery="$OPTARG"
	   ;;
	d) debug=1
	   ;;
	t) typeFilter="$OPTARG"
	   ;;
    esac
done

fq="docType_s:($typeFilter)"
structQuery="($structQuery) and docType_s:($typeFilter) and "

echo "Restriction structure/autre: $structQuery\n"
echo "Restriction typologie: $typeFilter\n"
echo "Restriction version: $VersionUnique\n"
echo
echo "Year |\tnotice\tfile\tannex\t| Total"
echo "-----+--------+--------+--------+-------"
for Year in 2010 2011 2012 2013 2014 2015 2016 ; do
    echo -n "$Year |"
    total=0
    for Type in 'notice' 'file' 'annex' ; do
	req="$url?q=${structQuery}$datefield:${Year}%0Aand%0A$VersionUnique and submitType_s%3A${Type}&$fq&fl=docid&wt=json&indent=true"
	[ $debug -eq 1 ] && echo $req;
	res=`wget -q -O - "$req" | grep numFound | sed 's/.*"numFound":\([0-9]*\),.*/\1/'`
	total=$((total + $res)) 
        echo -n "\t$res"
    done
    echo "\t| $total"
done
