# HAL

La plateforme HAL est une archive ouverte. Elle est destinée au dépôt et à la diffusion d'articles scientifiques de niveau
recherche, publiés ou non, et de thèses, émanant des établissements d'enseignement et de recherche français ou étrangers,
des laboratoires publics ou privés.

# REMARQUES SUR LE CODE

Hal n'a pas été développé dans l'esprit de permettre son installation par tout un chacun.  Il est fait pour tourner
"seulement" sur la plateforme Hal.archives-ouvertes.fr.

En conséquence, de nombreux hacks spécifiques à la plateforme ont été codés en dur, rendant une utilisation dans un
contexte différent très difficile, voire impossible.

Nous sommes en cours de nettoyage de ce code:
*    pour le rendre plus maintenable,
*    pour le rendre plus conforme aux bonnes pratiques,
*    pour rendre les sites Web accessibles au sens du WAI,
*    pour éliminer les problèmes de sécurité,
*    pour permettre une migration de Zend,
*    ...

Il n'est donc pas besoin de nous remonter les problèmes de ce type...

Le code est publié au téléchargement mais nous ne maintiendrons pas forcement une compatibilité ascendante dans un
premier temps.

Ce README est particulièrement minimaliste pour décrire l'installation...


# INSTALLER LES LIBRAIRIES NECESSAIRES

Utilisation de Composer pour installer les dépendances.

# CONFIGURER LES VIRTUAL HOSTS

Des templates de virtualhost et hosts sont disponibles dans config/templates.

# CONFIGURER L'ACCES AUX BASES DE DONNEES

Afin de faire fonctionner l'application, il faut configurer les variables d'accès aux différentes bases de données
utilisées par HAL :

+ Créer un fichier pwd.json qui doit se trouver dans le dossier config. Ce fichier est dans .gitignore pour s'assurer
qu'il reste en local.

+ Configurer ce fichier en respectant la structure suivante :
```json
{
    "HAL":{
        "HOST" : "",
        "PORT" : "",
        "NAME" : "",
        "USER" : "",
        "PWD" : ""
    },
    "CAS":{
        "SERVICE" : "",
        "HOST" : "",
	"PORT" : "",
        "NAME" : "",
        "USER": "",
        "PWD": ""
    },
    "SOLR":{
        "HOST" : "",
        "NAME" : "",
        "USER" : "",
        "PWD" : ""
    },
    "THUMB":{
        "HOST" : "",
        "NAME" : "",
        "USER" : "",
        "PWD" : ""
    },
    "ARXIV":{
        "SERVICE" : "",
        "USERAGENT" : "",
        "USER" : "",
        "PWD" : ""
    },
    "SWH":{
        "SERVICE" : "",
        "USERAGENT" : "",
        "USER" : "",
        "PWD" : ""
	},
    "STATS":{
        "HOST" : "",
        "PORT" : "",
        "NAME" : "",
        "USER" : "",
        "PWD" : ""
    },
    "REFBIBLIO": {
        "HOST" : "",
        "PORT" : "",
        "NAME" : "",
        "USER" : "",
        "PWD" : ""
    },
    "DBTABLE" : {
	"HOST" : "",
	"PORT" : "",
        "NAME" : "",
        "USER": "",
        "PWD": ""
    },
    "ORCID" : {
	"CLIENT_ID" : "",
	"CLIENT_SECRET" : "",
	"ENDPOINT" : "https://pub.orcid.org/oauth/token",
	"REDIRECT" : ""
    }
}
```

# CONFIGURER L'ACCES AU CACHE

+ Configurer une nouvelle variable d'environnement CACHE_ROOT dans la configuration d'apache : 
*	SetEnv CACHE_ROOT /var/www/cache/hal

+ Configurer un nouvel alias dans la configuration des Virtual Host d'apache :
*	Alias /cache /var/www/cache/hal/"development"

+ Changer les droits du dossier cache/ pour qu'apache soit propriétaire du groupe 
et qu'il ait les accès en écriture
*	chown -R user:www-data cache
*	chmod -R g+w cache

# CONFIGURER L'ACCES AUX DONNEES

+ Configurer une nouvelle variable d'environnement DATA_ROOT dans la configuration d'apache : 
*	SetEnv DATA_ROOT /var/www/data/hal

+ Configurer un nouvel alias dans la configuration des Virtual Host d'apache :
*	Alias /data /var/www/data/hal/development

+ Changer les droits du dossier data/ pour qu'apache soit propriétaire du groupe 
et qu'il ait les accès en écriture
*	chown -R user:www-data data
*	chmod -R g+w data

# CONFIGURER L'ACCES AUX DOCUMENTS
+ Configurer une nouvelle variable d'environnement DOCS_ROOT dans la configuration d'apache : 
* 	SetEnv DOCS_ROOT /var/www/docs

+ Changer les droits du dossier docs/ pour qu'apache soit propriétaire du groupe 
et qu'il ait les accès en écriture
*	chown -R user:www-data scdocs
*	chmod -R g+w scdocs

# CONFIGURER LES DONNEES

+ Pour pouvoir lancer le portail HAL, il faut les données de HAL !! A demander !!

# CONFIGURER LES SERVICES ANNEXES

* Thumb  : generateur et distributeur des vignettes
* Convert: service de compilation Latex
* xxx    : Service de convertion DOCX vers PDF
* CAS    : Pour authentification

# AUTEURS

* Laurent Capelli   <Laurent.Capelli@huma-num.fr>
* Yannick Barborini <yannick.barborini@huma-num.fr>
* Hélène Jamet
* Raphaël Tournoy   <raphael.tournoy@ccsd.cnrs.fr>
* Loic Comparet
* Laurence Farhi    <laurence.farhi@inria.fr>
* Maxime Cocquempot
* Baptiste Blondelle
* Valérian Calès
* Isabelle Guay     <isabelle.guay@ccsd.cnrs.fr>
* Sarah Denoux
* Kevin Loiseau
* Bruno Marmol      <bruno.marmol@ccsd.cnrs.fr>
* Zahen Malla Osman
* Meriam Fathallah
* Jean-Baptiste Genicot
* Theophane Kouchoanou
* ...

# LICENCE

HAL est publié par le CCSD/CNRS sous licence GNU GPLv3+.

Voir le fichier LICENSE dans ce dépôt.
