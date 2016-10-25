# Viaeduc

## Pré-Requis Serveur
Pour le bon fonctionnement de l'application, les logiciels suivants doivent être présents :  

* Apache
* PHP ≥ 5.4
* MySQL 5.x
* ElasticSearch < 2.0
* Etherpad 1.5.7

### Modules PHP

Les modules PHP suivants doivent être activés dans la configuration de PHP:  

* curl
* intl
* APC

### Autres

Les [pré-requis du framework Symfony](http://symfony.com/doc/2.5/reference/requirements.html) doivent également être pris en compte.

## Architecture de l'application

L'application est basée sur le framework PHP [Symfony](http://symfony.com) en version 2.5.0, elle suit donc son arborescence ainsi que ses conventions de nommage et de développement.

### Librairies

En complément du framework et de ses propres pré-requis, l'application a également 3 librairies requises :

* `mobiledetect/mobiledetectlib` — v2.7.* — Bundle permettant de détecter le type d'appareil utilisé
* `exercise/htmlpurifier-bundle` — dev version (commit `cffa450`) — Bundle permettant de nettoyer du contenu HTML
* `pum/pum` — dev version (commit `b2a7831`) — Bundle Symfony fournissant un Backoffice permettant de gérer à la fois la structure de la base de donnée en tant qu'objets via une interface, mais également les différentes données, vues, et utilisateurs y ayant accès.
* `jasig/phpcas` - 1.3.3 - Bundle permettant de s'interfacer avec du CAS
* `friendsofsymfony/rest-bundle` - 1.5.0-RC2 - Bundle fournissant un socle et méthodes pour API REST
* `liip/url-auto-converter-bundle` - 1.1.0 - Bundle permettant d'ajouter un filtre Twig pour détecter et convertir les mails et urls _(présente mais actuellemet non utilisée)_
* `candy-chat/xmpp-prebind-php` - dev version (commit `76f7382`) - Bundle permettant de s'interfacer avec du XMPP
* `hashids/hashids` - 1.0.5 - Bundle permettant de hasher des IDs


#### Composer

L'installation des dépendances de l'application s'installent de la même façon que celles du framework : via [Composer](https://getcomposer.org).  
Dans le cas où les sources sont fournies avec le dossier `vendor` rempli, il n'est pas nécessaire de lancer Composer, sauf en cas de mise à jour d'une ou plusieurs dépendances.
Le fichier `composer.lock` fournit un état figé des versions des dépendances de l'application.

### Sources

Les sources propres à l'application sont réparties dans 2 répertoires :

* `app/config/` — contient les fichiers de configuration de l'application. Le fichier de base pour une installation locale est le fichier ayant l'extension `.dist`.  
* `src/Rpe` — contient la totalité des développements PHP spécifiques à l'application Viaeduc.

#### `src/Rpe`

Le dossier est découpé en différents sous-dossiers permettant d'étendre ou surcharger les différents Bundles liés à l'application :

* `PumBundle` — Contient tout le code nécessaire au fonctionnement de l'application Playbook côté utilisateur (=hors backoffice)
* `RestBundle` — Concerne tout ce qui est relatif à l'API et au bundle `rest-bundle`

### Frontend

La partie Frontend se trouve dans le répertoire `src/Rpe/PumBundle/Resources`.
Les styles CSS sont compilés via le pré-processeur [Sass](http://sass-lang.com/) couplé au framework [Compass](http://compass-style.org/).  

Il n'est cependant pas nécessaire d'installer et d'utiliser ces outils pour faire fonctionner l'application.  
Ils ne sont nécessaire que localement lorsqu'une modification doit être effectuée au niveau d'une dépendance frontend ou des assets *(js,css)*.

## Installation

1. Déposer le code source dans un dossier
2. Créer la base de donnée (et importer un dump SQL si existant)
3. Dupliquer le fichier `app/config/parameters.yml.dist` en `app/config/parameters.yml`, modifier les paramètres de base de donnée (`database_host`, `database_name`, `database_user` et `database_password`) et le paramètre `secret`.
4. Appliquez les droits d'execution sur `app/console`.
5. Exécuter le script `./reset.sh` depuis la racine du projet
6. Configurer un *vhost* pointant vers le dossier `web/`

## Base de données

La base de donnée est composée de deux types de tables :

* Les tables des données spécifiques à l'application, toutes préfixées par `obj__`, et gérées via la partie *Woodwork*
* Les tables de données concernant le backoffice et la structure des données *(projets, beams, objets)*, il s'agit de toutes les autres tables.

## Elasticsearch
Par défaut, l'application utilise Elasticsearch via localhost:9200.
Il est néanmoins possible d'utiliser un autre host/port via les fichiers de configuration de l'application, comme précisé [ici](https://github.com/KitaeAgency/pum/blob/master/doc/elasticsearch/params.rst).

Il peut être nécessaire de régénérer l'index Elasticsearch en lançant la commande:
```
php app/console pum:search:regenerateindex
```

## Etherpad
L'url d'Etherpad doit être indiquée dans le fichier `app/config/parameters.yml`.

```yaml
parameters
	…
    etherpad:
        base_url:      http://etherpad.your_host.tld/
        api_key:       etherpad_api_key
        domain_cookie: .your_host.tld
```

Etherpad doit être à minima sur un sous-domaine du domaine principal de Viaeduc, afin que les cookies puissent être partagés.

## Logs
Gérés par le framework Symfony, des logs des environnements d'exécution "dev" et "prod" du framework sont disponibles dans `app/logs/`.

## PHPDoc
Il est possible de générer une documentation du code source via PHPDoc.

 - Télécharger [PHPDoc](http://www.phpdoc.org/)
 - Exécuter `php phpDocumentor.phar -p` depuis la racine du projet
