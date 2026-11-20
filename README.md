# TntSearch

Moteur de recherche de Thelia 3 fondé sur la bibliothèque [TNTSearch](https://github.com/teamtnt/tntsearch). Il implémente le contrat de recherche du cœur (`Thelia\Domain\Search\Contract\SearchEngineInterface`) et ne demande aucun serveur : les index sont des fichiers SQLite écrits par l'application.

## Ce que fait le module

- **Recherche du catalogue** (produits, catégories, contenus, dossiers, marques) pour le service de recherche du cœur, donc pour tout thème qui l'appelle (Flexy compris).
- **Tolérance aux fautes** : une lettre d'écart pour un mot de moins de 6 lettres, deux au-delà (« Scralett » trouve « Scarlett », « divan » ne trouve pas « diam »), comptées en caractères, accents et alphabets non latins compris. Un mot trouvé malgré une faute compte moitié moins qu'un mot exact. Une faute dans les deux premières lettres n'est pas corrigée, et seuls les 50 mots les plus fréquents qui commencent par ces deux lettres sont comparés.
- **Pertinence pondérée** : chaque champ compte proportionnellement au poids que le cœur lui donne (titre 10, référence et chapeau 5, marque 3, autres champs 1).
- **Autocomplétion** : le dernier mot tapé trouve aussi les mots qui commencent par lui (« chau » trouve « chaussures »), avec un score réduit.
- **Racinisation et mots vides** par langue (français, italien, allemand, portugais, russe ; anglais par défaut).
- **Index des clients et des commandes**, propres au module et lus par son service public `tntsearch.search`. Aucun écran du back-office ne les interroge : la recherche du back-office (`/admin/search`) est celle du thème, qui cherche en base sans passer par le module.
- **Journal des recherches** dans la table `tnt_search_log`, lu par le back-office.

Les synonymes et les redirections de recherche sont ceux du cœur, gérés dans leurs écrans du back-office et appliqués avant d'interroger le moteur.

## Prérequis

- Thelia 3.5 ou plus récent.
- Extension PHP `pdo_sqlite`.
- Un dossier `local/TNTIndexes/<APP_ENV>/` accessible en écriture par PHP, **sur un disque local**. SQLite (journal WAL) et les verrous `flock` du module ne sont pas fiables sur un partage réseau (NFS, SMB, volume partagé d'un cluster) : ne pas y placer ce dossier.
- Un seul serveur web, avec ses workers sur la même machine. Sur plusieurs serveurs, chacun aurait ses propres index, et une écriture ne mettrait à jour que ceux du serveur qui la traite : brancher alors un moteur à serveur (adaptateur du contrat du cœur).

## Sécurité

Le dossier `local/TNTIndexes/` contient le texte indexé du catalogue, y compris des produits invisibles, et les index des clients et des commandes : noms, adresses électroniques, références de commande. Ce sont des données personnelles.

- Le dossier est hors de la racine web (`public/`) : ne jamais l'y déplacer, ni le rendre accessible par un alias du serveur web.
- Droits limités à l'utilisateur de PHP (`chmod 700` sur le dossier).
- L'exclure des sauvegardes qui sortent de l'hébergement, ou les chiffrer : il se reconstruit par `tntsearch:indexes`.
- La page `/admin/search_log` du module exige le droit de voir les commandes (`admin.order`), comme le rapport des recherches du thème.
- À la suppression du module avec ses données, les index de documents sont effacés ; supprimer aussi `local/TNTIndexes/` pour les index des clients et des commandes.

## Installation

```bash
composer require thelia/tnt-search-module
php bin/console module:activate TntSearch
php bin/console thelia:search:reindex
```

L'activation en ligne de commande construit les index. Activé depuis le back-office, ou après la montée en 5.0.0, le module ne reconstruit jamais le catalogue pendant une requête web : la première requête du back-office confie la reconstruction une seule fois à la file `async_heavy` si une file est configurée (`MESSENGER_TRANSPORT_DSN`), et sinon journalise qu'il faut lancer `thelia:search:reindex`. `tntsearch:status` affiche la date de cette mise en file.

## Indexation

Le module n'écoute plus lui-même les écritures du catalogue : c'est le cœur qui signale chaque changement (back-office, API, import, module) et l'envoie au moteur, en file de messages ou immédiatement selon `MESSENGER_TRANSPORT_DSN`. Une écriture faite par une requête Propel en masse (`update()`, `delete()` sur une requête) n'émet rien : lancer `thelia:search:reindex --purge` après.

Les index des clients et des commandes restent construits par le module. Le réglage « Mettre à jour l'index des clients à chaque enregistrement d'un compte client » de sa configuration ne concerne que cet index.

`tntsearch:indexes` (et le bouton de la configuration du module) reconstruit tout : les index des clients et des commandes, puis le catalogue. La première requête du back-office qui ne trouve pas l'index des clients ne reconstruit que les index des clients et des commandes.

## Diagnostic

```bash
php bin/console tntsearch:status
```

Affiche le dossier des index, le moteur qui répond au cœur, puis pour chaque famille et chaque langue le nombre de documents et la date de la dernière écriture. La commande sort en erreur tant qu'aucun index du catalogue n'existe.

Le moteur ne répond dans une langue qu'une fois chaque famille du catalogue qui a des éléments reconstruite **jusqu'au bout** dans cette langue : la fin de `thelia:search:reindex` (ou, avec `--queue`, un dernier message passé après les lots), de `tntsearch:indexes` ou d'une reconstruction mise en file par le module pose un marqueur `document--<famille>--<langue>.complete` à côté des index. Une purge l'efface. Le marqueur ne vaut que si le fichier d'index de la langue existe, sauf pour une langue qui n'avait aucun document au moment du marquage : un index supprimé à la main laisse sa langue à la base au lieu de rendre des résultats vides. Une langue ajoutée n'en a pas encore : la base répond dans cette langue seule, le moteur continue de répondre dans les autres, et la requête suivante du back-office remet le catalogue en file (au plus une fois toutes les 6 h). Avec `--queue`, le marqueur attend que la file `async_heavy` soit vide (il repasse toutes les 30 s, une heure au plus, puis renonce et la base répond) ; un lot de reconstruction (file `async_heavy`) qui échoue pour de bon efface le marqueur de sa famille, alors qu'une écriture courante en échec (file `async`) ne touche pas aux marqueurs et laisse une trace `document--<famille>.failed` : le marqueur n'est plus reposé pour cette famille (avertissement au journal) jusqu'au début de la reconstruction suivante (`thelia:search:reindex`, `tntsearch:indexes` ou mise en file par le module), qui efface la trace. Chaque activation du module supprime ses index de documents et leurs marqueurs (pas les fichiers `.lock`, qu'un processus peut encore tenir), qui sont reconstruits comme à la première activation : un index d'avant la désactivation n'est jamais servi. La suppression du module avec ses données les supprime aussi. D'ici là, ou si SQLite manque, le cœur fait répondre la base : la boutique reste utilisable, sans tolérance aux fautes. Pendant une requête, une même recherche n'est faite qu'une fois : les pages suivantes la relisent jusqu'à la prochaine écriture d'index. Un index illisible coupe le moteur 30 s pour toute la boutique, et la base répond pendant ce temps. Un index seulement occupé par une écriture (SQLite « database is locked » après 250 ms) ne fait échouer que la recherche en cours, qui retombe sur la base, sans couper le moteur. Une écriture attend au plus 10 s le verrou de sa famille, puis échoue (le message est rejoué par la file). Un catalogue mis en file par le module et toujours absent 6 h plus tard est remis en file à la requête suivante du back-office. Une recherche dans une langue que le cœur n'indexe pas (inactive ou exclue par `search_exclude_locales`) est faite par la base.

## Monter de version vers 5.0.0

La 5.0.0 remplace la recherche propre au module par le contrat du cœur.

- Les anciens index du catalogue (`product_fr_FR.index`, etc.) sont supprimés. Lancer `thelia:search:reindex` (ou laisser la file le faire, voir Installation) ; d'ici là, la base répond.
- Les groupes de synonymes du module sont copiés dans le dictionnaire du cœur, dans chaque langue active, en synonymes réciproques. Un terme que le dictionnaire du cœur connaît déjà est laissé tel quel. La table `tnt_synonym` est conservée mais n'est plus lue, et l'écran des synonymes du module disparaît.
- Le module ne déclare plus de moteur de recherche propre à Flexy : Flexy passe par le service de recherche du cœur.
- `WeightEvent` et `ExtendQueryEvent` ne s'appliquent plus qu'aux index des clients et des commandes. Les poids du catalogue se règlent dans les fournisseurs de documents du cœur.
- Les anciens synonymes ne s'appliquent plus aux index des clients et des commandes : seuls ceux du cœur existent, et ils ne concernent que le catalogue.
- Le module ne fournit plus de page `/admin/search` : c'est celle du thème du back-office qui répond.

## Tests

Depuis la racine de l'installation Thelia :

```bash
composer phpc -- ./vendor/bin/phpunit -c local/modules/TntSearch/phpunit.xml.dist
```

## Licence

GPL-3.0-or-later.
