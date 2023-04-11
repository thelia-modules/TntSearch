# Tnt Search

This module provides TNTSearch feature to Thelia. [TNTSearch](https://github.com/teamtnt/tntsearch) is a full-featured full text search (FTS) engine written entirely in PHP. 

The module could be used in front-office, using the dedicated loop. It also replaces the standard back-office search.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is TntSearch.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/tnt-search-module:~0.6.1
```

## Configuration

The search indexes will be updated each time a product, category, folder, content,
brand, ... is updated in the back-office. This could take some time, depending on your configuration.
You can disable this reali-time udate in the module configuration, to speed up 
back-office changes.


In this case, you have to rebuild the indexes manually, using the `Rebuild Indexes` button in
the module configuiration page, or automatically using a cron which trigger the index 
build from time to time with this Thelia command: `Thelia tntsearch:indexes`


## Thelia Loops

### tnt-search loop

This loops return ids of the elements selected.

### Input arguments

| Arguments        | Description                                                                                                                |
|------------------|----------------------------------------------------------------------------------------------------------------------------|
| ***search_for*** | A list of elements you want to search for (`product`, `category`, `folder`, `content`, `brand`, `order` or `customer`) |
| ***locale***     | A list of lang you want to search on ex: 'fr_FR, en_US'                                                                    |
| ***search***     | The search term to look for                                                                                                |

### Output arguments

| Variable   |Description |
|------------|--- |
| $PRODUCT   |A list of product ids or 0 |
| $CATEGORY  |A list of category ids or 0 |
| $BRAND    |A list of brand ids or 0 |
| $FOLDER   |A list of folder ids or 0 |
| $CONTENT  |A list of content ids or 0 |
| $CUSTOMER |A list of customer ids or 0 |
| $ORDER    |A list of order ids or 0 |

### Example

To use this loop you need to combine it with another loop
Index available : product,brand,category,folder,content,customer,order

    {loop type="tnt-search" name="product-tnt-search-loop" search_for="product" locale="fr_FR" search=$search}
        {loop type="product" name="product-loop" id=$PRODUCT order="given_id"}
            Put your code here
        {/loop}
    {/loop}

The `order="given_id"` is important because TNTSearch return the ids in order of pertinence.

### Example
