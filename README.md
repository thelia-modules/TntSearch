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
You can disable this reali-time update in the module configuration, to speed up 
back-office changes.


In this case, you have to rebuild the indexes manually, using the `Rebuild Indexes` button in
the module configuration page, or automatically using a cron which trigger the index 
build from time to time with this Thelia command: `Thelia tntsearch:indexes`

## Indexation

The indexing of the content of the site for the search is done with two different tokenization:
- one will index the radical of each word found in the different entities. Ideal for searching in **long texts** like product descriptions.
    #### Example
        - field's text :  "classical and designed chairs" 
        - indexes :       [ "classic", "design", "chair" ]

- the other will allow a more advanced search by tokenizing each word character by 
character (min. 2 characters). Ideal short texts fields.
    #### Example
        - field's text :  "Horatio"
        - indexes :       [ "ho", "hor", "hora", "horat", "horati", "horatio"]

You can find, add or remove fields with more complex tokenization in the ```Service/Support/TntIndexer file```.

  ### Processing specifics:
  Some fields have a specific processing:
  - **ref**  and  **pse_ref**:  The '-' and '_' are removed from ref and pse_ref fields when they are
  indexed and should be removed from user input! And the "." will cut off it.
    #### Example
          - field's text :  "REF-74_2.32"
          - indexes :       [ "re", "ref", "ref7", "ref74" ", " ref742 ", "32" ]
  - the customer's **email** field has been queried twice to benefit from the 2 types of tokenization: 
  once on the whole string and the second on the part cut before the "@".
    #### Example
            - email:    "test@mail.com"
            - indexes:  [ "test@mail.com", "te", "tes", "test" ]

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
            *** Put your code here ***
        {/loop}
    {/loop}

The `order="given_id"` is important because TNTSearch return the ids in order of pertinence.

