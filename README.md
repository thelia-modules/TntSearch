# Tnt Search

This module replace the search algorithm of the back office and the front-office by the TntSearch library.

## Installation

### Composer

This module include dependencies needs to be installed by composer

Add it in your main thelia composer.json file

```
composer require thelia/tnt-search-module:~0.6.1
```

## Thelia Loops

### tnt-search loop

This loops return ids of the elements selected.

### Input arguments

|Arguments |Description|
|--- |--- |
|***search_for*** |A list of elements you want to search for (`products`, `categories`, `folders`, `contents`, `brands`, `orders` or `customers`)|
|***langs*** |A list of lang you want to search on ex: 'fr_FR, en_US'|
|***search*** |The search term to look for|

### Output arguments

|Variable  |Description |
|--- |--- |
|$PRODUCTS |A list of product ids or 0 |
|$CATEGORIES |A list of category ids or 0 |
|$BRANDS |A list of brand ids or 0 |
|$FOLDERS |A list of folder ids or 0 |
|$CONTENTS |A list of content ids or 0 |
|$CUSTOMERS |A list of customer ids or 0 |
|$ORDERS |A list of order ids or 0 |

### Example

To use this loop you need to combine it with another loop

    {loop type="tnt-search" name="product-tnt-search-loop" search_for="products" langs="fr_FR" search=$search}
        {loop type="product" name="product-loop" id=$PRODUCTS order="given_id"}
            Put your code here
        {/loop}
    {/loop}

The `order="given_id"` is important because TNTSearch return the ids in order of pertinence.


# TNT Search

Ce module remplace l'algorithme de recherche du back office et du front-office par la librairie TntSearch.

## Installation

### Composer

Ce module comporte des dépendances et doit être installé via composer

Ajoutez-le dans votre fichier principal thelia composer.json

''
composer nécessite thelia / tnt-search-module: ~ 0.6.0
''

## Boucles Thelia

### boucle de recherche tnt

Cette boucle renvoie les identifiants des éléments sélectionnés.

### Arguments d'entrée

| Arguments | Description |
| --- | --- |
| *** search_for *** | Une liste des éléments que vous voulez rechercher (`produits`,` catégories`, `dossiers`,` contenus`, `marques`,` commandes` ou `clients`) |
| *** langs *** | Une liste de langues sur lesquelles vous souhaitez rechercher ex: 'fr_FR, en_US' |
| *** recherche *** | Le terme de recherche à rechercher |

### Arguments de sortie

| Variable | Description |
| --- | --- |
| $ PRODUCTS | Une liste d'ID produits ou 0 |
| $ CATEGORIES | Une liste d'identifiants de catégorie ou 0 |
| $ BRANDS | Une liste d'identifiants de marque ou 0 |
| $ FOLDERS | Une liste d'ID de dossier ou 0 |
| $ CONTENTS | Une liste d'ID de contenu ou 0 |
| $ CUSTOMERS | Une liste d'identifiants clients ou 0 |
| $ ORDERS | Une liste d'ID de commande ou 0 |

### Exemple

Pour utiliser cette boucle, vous devez la combiner avec une autre boucle

    {loop type = "tnt-search" name = "product-tnt-search-loop" search_for = "products" langs = "fr_FR" search = $ search}
        {loop type = "product" name = "product-loop" id = $ PRODUCTS order = "given_id"}
            Mettez votre code ici
        {/boucle}
    {/boucle}

Le `order =" given_id "` est important car TNTSearch renvoie les identifiants par ordre de pertinence.
