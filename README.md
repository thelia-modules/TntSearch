# TNT Search Module

This module integrates TNTSearch, a full-featured full text search engine written in PHP, into Thelia. It works both in the front-office through a dedicated loop and replaces the standard back-office search functionality.

## Installation

Add it to your main Thelia composer.json file:

```bash
composer require thelia/tnt-search-module:~2.0
```

## Configuration

Search indexes update automatically when products, categories, folders, content, brands, etc. are modified in the back-office. For faster back-office operations, you can disable real-time updates in the module configuration.

When real-time updates are disabled, you'll need to rebuild indexes manually:
- Use the "Rebuild Indexes" button in the module configuration page
- Set up a cron job with the command: `Thelia tntsearch:indexes`

## Thelia Loops

### tnt-search loop

Returns IDs of the elements matching your search criteria.

#### Input arguments

| Argument | Description |
|----------|-------------|
| **search_for** | Elements to search for (`product`, `category`, `folder`, `content`, `brand`, `order` or `customer`) |
| **locale** | Languages to search in (e.g., 'fr_FR, en_US') |
| **search** | Search term |

#### Output arguments

| Variable | Description |
|----------|-------------|
| $PRODUCT | List of product IDs or 0 |
| $CATEGORY | List of category IDs or 0 |
| $BRAND | List of brand IDs or 0 |
| $FOLDER | List of folder IDs or 0 |
| $CONTENT | List of content IDs or 0 |
| $CUSTOMER | List of customer IDs or 0 |
| $ORDER | List of order IDs or 0 |

#### Example

To use this loop, combine it with another loop:

```smarty
{loop type="tnt-search" name="product-tnt-search-loop" search_for="product" locale="fr_FR" search=$search}
    {loop type="product" name="product-loop" id=$PRODUCT order="given_id"}
        Put your code here
    {/loop}
{/loop}
```

The `order="given_id"` parameter is important to preserve the relevance order provided by TNTSearch.

## Custom Indexation

To implement custom indexation, create a class implementing `TntSearchIndexInterface` and register it as a service with the `tntsearch.base.index` parent.

### TntSearchIndexInterface

If you want to create your own index, implement this interface which requires the following methods:

| Method | Description |
|--------|-------------|
| `getFieldWeights(string $field)` | Returns the weight for a given field (integer). Higher values give more importance to matches in this field. |
| `isTranslatable()` | Returns whether the indexed content has translations (boolean). |
| `isGeoIndexable()` | Returns whether the index supports geolocation search features (boolean). |
| `buildSqlQuery(int $itemId = null, string $locale = null)` | Returns the SQL query used to retrieve the data to index. Can be filtered by ID and locale. |
| `buildSqlGeoQuery(int $itemId = null)` | Returns the SQL query for geolocation data (or null if not applicable). |

### Service Configuration

Register your custom index as a service by extending the `tntsearch.base.index`.
This will automatically register your index with the TNTSearch indexation provider system.