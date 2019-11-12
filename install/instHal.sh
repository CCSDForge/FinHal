#!/usr/bin/env bash

# TODO: créer le répertoire pour les logs de php + dire de donner les droits à nobody: (au lieu de www-data) si serveur -> argument ?

function usage() {
    echo "Usage: ./instHal.sh [-c] -U <URL> -p <portail> -e <environment> -m <adminstrator_mail>"
    echo "Option:"
    echo "    -c: Tell git to check certificate"
}

function warnings() {
    echo "WARNINGS"
    echo "1. The installed applications will need ccsd/static to be accessible. I will make the Apache conf for it but you might need to clone the project in $ROOTDIR/static (or to modify path in install/confs/ccsdlib-instance.conf afterward)."
    echo ""
    echo "2. I am mainly design for a local install. If you are installing on server:
     2.1. you should check all pathes in generated Apache confs,
     2.2. you may have to modify Apache group/user name in commands I'll prompt you to run,
     2.3. you may want to change names and domain of the applications in $ROOTWWW/config/ApacheConf-basics.cnf"
    echo ""
    echo "3. Composer will check for some php extensions but can't install them. You might want to run the following command beforehand (NB: make sure that the version of php match yours) :"
    echo "    sudo apt install php7.2 php7.2-imagick php7.2-bz2 php7.2-curl php7.2-gd php7.2-json php7.2-mbstring php7.2-readline php7.2-soap php7.2-xml php7.2-zip php7.2-intl php7.2-geoip php7.2-ssh2"
    echo ""
}

function sudoCommands() {
    echo ""
    echo "1. CACHE"
    echo "You might need to give Apache group ownership of cache directory and give the group permission to write"
    echo "    sudo chown -R $(whoami):www-data $ROOTDIR/cache"
    echo "    sudo chmod -R g+w $ROOTDIR/cache"
    echo ""
    echo "2. DATA"
    echo "You might need to give Apache group ownership of data directory and give the group permission to write"
    echo "    sudo chown -R $(whoami):www-data $ROOTDIR/data"
    echo "    sudo chmod -R g+w $ROOTDIR/data"
    echo ""
    echo "3. DOCS"
    echo "You might need to give Apache group ownership of docs directory and give the group permission to write"
    echo "    sudo chown -R $(whoami):www-data $ROOTDIR/docs"
    echo "    sudo chmod -R g+w $ROOTDIR/docs"
    echo ""
    echo "4. HOST"
    echo "4.1 You need to copy all install/confs/*-instance.conf to your '/etc/apache2/sites-available' (path can differ) and run (ex. for Ubuntu):"
    echo "    sudo cp confs/*-instance.conf /etc/apache2/sites-available/"
    echo "    sudo a2ensite *-instance.conf"
    echo "4.2 You should make sure that modules 'rewrite, 'macro' and 'php' are enabled by running:"
    echo "    sudo a2enmod macro"
    echo "    sudo a2enmod rewrite"
    echo "    sudo a2enmod php*"
    echo "*to be replaced by the php version you are running (ex: 'sudo a2enmod php7.2')"
    echo ""
    echo "5. HOST"
    echo "You might need to add to '/etc/hosts' (path can differ) the lines (for localhost):"
    echo "    127.0.0.1    ${URL}"
    echo "You might also need to add to '/etc/hosts' the following lines for the applications. You juste have to make sure that the addresses are those declared in 'config/ApacheConf-basics.cnf'):"
    echo "    127.0.0.1    ccsdlib-local.ccsd.cnrs.fr"
    echo "    127.0.0.1    api-local.ccsd.cnrs.fr"
    echo "    127.0.0.1    cv-local.ccsd.cnrs.fr"
    echo "    127.0.0.1    aurehal-local.ccsd.cnrs.fr"
    echo ""
    echo "6. PHP"
    echo "You might modify your 'php.ini' following this model:"
    echo "    [Date]"
    echo ""
    echo "    ; Defines the default timezone used by the date functions"
    echo "    ; http://php.net/date.timezone"
    echo "    date.timezone = \"Europe/Berlin\""
    echo ""
    echo "    ; http://php.net/date.default-latitude"
    echo "    date.default_latitude = 45.7825"
    echo ""
    echo "    ; http://php.net/date.default-longitude"
    echo "    date.default_longitude = 4.865278 "
    echo ""
    echo "7. You should now restart Apache:"
    echo "    sudo systemctl restart apache2"
    echo ""
    echo "8. DATABASES ACCESS"
    echo "You need finally to create the file '${ROOTWWW}/config/pwd.json' by modifying the '${ROOTWWW}/config/templates/template.pwd.json'"
    echo ""
    echo "Done."
}

function parseOpts() {
    if [ "$#" -lt 8 ]; then
        usage

        read -p 'URL (default: halv3-local.ccsd.cnrs.fr): ' URL
        if [ -z "$URL" ]; then
            URL="halv3-local.ccsd.cnrs.fr"
        fi

        read -p 'portail (default: hal): ' PORTAIL
        if [ -z "$PORTAIL" ]; then
            PORTAIL="hal"
        fi

        read -p 'environment (default: development): ' ENV
        if [ -z "$ENV" ]; then
            ENV="development"
        fi

        read -p 'mail of the administrator: ' MAIL

        read -p 'Tell git to check certificate (y/n)? ' SAFE
        if [ "$SAFE" == "y" ]; then
            printf "\nNext time you could use: $0 -c -U $URL -p $PORTAIL -e $ENV -m $MAIL\n"
        else
            printf "\nNext time you could use: $0 -U $URL -p $PORTAIL -e $ENV -m $MAIL\n"
        fi
        echo ""
    fi

    while getopts 'U:p:e:m:ch' flag; do
      case "${flag}" in
        U)
            URL=${OPTARG}
            ;;
        p)
            PORTAIL=${OPTARG}
            ;;
        e)
            ENV=${OPTARG}
            ;;
        m)
            MAIL=${OPTARG}
            ;;
        h)
            usage
            ;;
        c)
            echo "Safe mode"
            SAFE='y'
            ;;
        *)
            usage
            exit "Unexpected option ${flag}"
            ;;
      esac
    done
}

function composer_setup() {

    if [ "$1" == "n" ] || [ "$1" == "no" ] || [ "$1" == "non" ]; then
        export GIT_SSL_NO_VERIFY=1
    fi

    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")"

    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
    then
        >&2 echo 'ERROR: Invalid installer signature'
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php
    echo -e $RESULT
}

function create_dirs() {
    mkdir -p $1/$2
    current_dir="$1/$2"
    shift 2
    if [ ! "$#" -eq 0 ]; then
        create_dirs $current_dir $@
    fi

}


if [[ $(basename $(pwd)) != "install" ]] ; then
    echo "Please go to 'install' directory before running me."
    exit 1
fi

ROOTDIR="$(dirname $(dirname $(pwd)))"
ROOTWWW="$(dirname $(pwd))"
URL=''
PORTAIL=''
ENV=''
MAIL=''
SAFE='n'

warnings
parseOpts $@

composer_setup "$SAFE"

cd ..
php install/composer.phar install
cd install

create_dirs "$ROOTDIR" cache "$PORTAIL"
create_dirs "$ROOTDIR" data "$PORTAIL" "$ENV" portail "$PORTAIL" config
mkdir -p "$ROOTDIR/docs"
cp navigation.json "$ROOTDIR/data/$PORTAIL/$ENV/portail/$PORTAIL/config/navigation.json"
ln -s ../vendor/ccsd/library/scripts ../scripts/library

# Supprimer les erreurs !
ln -s ../vendor/ccsd/library/Ccsd ../library/Ccsd
ln -s ccsd/library ../vendor/library


# FUSIONNER LES 2 PAGES D'INSTALL DU WIKI ET METTRE À JOUR
# METTRE UN EXEMPLE DE navigation.json SUR LE WIKI


printf "
DEFINE ROOTDIR ${ROOTDIR}
DEFINE ROOTWWW ${ROOTWWW}
DEFINE URL ${URL}
DEFINE PORTAIL ${PORTAIL}
DEFINE ENV ${ENV}
DEFINE MAIL ${MAIL}
" > ../config/ApacheConf-basics.cnf
cat ./confs_templates/basics.cnf >> ../config/ApacheConf-basics.cnf
echo "The file ApacheConf-basics.cnf has been added to the config directory."

mkdir -p confs
for conf in ./confs_templates/*-instance.conf
do
    printf "DEFINE ROOTWWW ${ROOTWWW}\n\n" > ./confs/${conf##*/}
    cat $conf >> ./confs/${conf##*/}
    echo "The file install/confs/${conf##*/} has been created."
done

sudoCommands
