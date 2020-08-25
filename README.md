# Tnt Search

This module replace the search algorithm of the back office by the TntSearch library.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is TntSearch.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/tnt-search-module:~0.6.0
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
