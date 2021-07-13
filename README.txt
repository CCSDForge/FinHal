======= HAL ========

La plateforme HAL est une archive ouverte. Elle est destinée au dépôt et à la diffusion d'articles scientifiques de niveau recherche, publiés ou non, et de thèses, émanant des établissements d'enseignement et de recherche français ou étrangers, des laboratoires publics ou privés.

======= INSTALLER LES LIBRAIRIES NECESSAIRES ========


+ php>=5.4
+ php-mbstring
+ apache>=2.0
+ php-xsl>=5.4
+ php-mysql
+ php-curl
+ php-xml
+ php-zip
+ php-imagick
+ php-geoip
+ php-curl>=5.4
+ mysql>=5.4
+ Solarium>=2.0
+ Symfony>=2.0
+ Zend=1.12.17
+ ZendX=0.0

======= CONFIGURER LES VIRTUAL HOSTS ========

Des templates de virtualhost et hosts sont disponibles dans config/templates.

======= CONFIGURER L'ACCES AUX BASES DE DONNEES ========

Afin de faire fonctionner l'application, il faut configurer les variables d'accès aux différentes bases de données utilisées par HAL :

+ Créer un fichier pwd.json qui doit se trouver dans le dossier config. Ce fichier est dans .gitignore pour s'assurer qu'il reste en local.

+ Configurer ce fichier en respectant la structure suivante :

{
    "production":{
        "DB":{
			"HOST" : "",
			"NAME" : "",
			"USER" : "",
			"PWD" : ""
        },
        "CAS":{
        }
    },
    "demo":{
        "DB":{
        },
        "CAS":{
        }
    },
    "testing":{
        "DB":{
        },
        "CAS":{
        }
    },
    "development":{
        "DB":{
        },
        "CAS":{
        }
    }
}

Pour chaque sous-partie, il faut définir 4 paramètres :
"HOST" : adresse de la base, "NAME" : nom de la base, "USER" : login, "PWD" : password

======= CONFIGURER L'ACCES AU CACHE ========

+ Configurer une nouvelle variable d'environnement CACHE_ROOT dans la configuration d'apache : 
	SetEnv CACHE_ROOT /var/www/cache/hal

+ Configurer un nouvel alias dans la configuration des Virtual Host d'apache :
	Alias /cache /var/www/cache/hal/"development"

+ Changer les droits du dossier cache/ pour qu'apache soit propriétaire du groupe 
et qu'il ait les accès en écriture
	chown -R user:www-data cache
	chmod -R g+w cache

======= CONFIGURER L'ACCES AUX DONNEES ========

+ Configurer une nouvelle variable d'environnement DATA_ROOT dans la configuration d'apache : 
	SetEnv DATA_ROOT /var/www/data/hal

+ Configurer un nouvel alias dans la configuration des Virtual Host d'apache :
	Alias /data /var/www/data/hal/development

+ Changer les droits du dossier data/ pour qu'apache soit propriétaire du groupe 
et qu'il ait les accès en écriture
	chown -R user:www-data data
	chmod -R g+w data

======= CONFIGURER L'ACCES AUX DOCUMENTS ========

+ Configurer une nouvelle variable d'environnement DOCS_ROOT dans la configuration d'apache : 
	SetEnv DOCS_ROOT /var/www/docs

+ Changer les droits du dossier docs/ pour qu'apache soit propriétaire du groupe 
et qu'il ait les accès en écriture
	chown -R user:www-data scdocs
	chmod -R g+w scdocs

====== CONFIGURER LES DONNEES =======
+ Pour pouvoir lancer le portail HAL, il faut les données de HAL !! A demander !!


====== AUTEURS ======

Laurent Capelli <Laurent.Capelli@huma-num.fr>
Yannick Barborini <yannick.barborini@huma-num.fr>
Hélène Jamet
Raphaël Tournoy <raphael.tournoy@ccsd.cnrs.fr>
Loic Comparet
Laurence Farhi <Laurence Farhi <laurence.farhi@inria.fr>
Maxime Cocquempot
Baptiste Blondelle <baptiste.blondelle@ccsd.cnrs.fr>
Valérian Calès
Isabelle Guay <isabelle.guay@ccsd.cnrs.fr>
Sarah Denoux <sarah.denoux@ccsd.cnrs.fr>
Kevin Loiseau <kevin.loiseau@ccsd.cnrs.fr>
Bruno Marmol <bruno.marmol@ccsd.cnrs.fr>
Zahen Malla Osman <zahen.mallaosman@ccsd.cnrs.fr>
