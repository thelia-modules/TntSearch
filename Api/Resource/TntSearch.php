<?php

namespace TntSearch\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Thelia\Api\Resource\Brand;
use Thelia\Api\Resource\Category;
use Thelia\Api\Resource\Content;
use Thelia\Api\Resource\Customer;
use Thelia\Api\Resource\Folder;
use Thelia\Api\Resource\Order;
use Thelia\Api\Resource\Product;
use TntSearch\Api\Provider\TntSearchProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/tnt-search',
            openapiContext: [
                'parameters' => [
                    [
                        'name' => 'search',
                        'in' => 'query',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                        'description' => 'search term'
                    ],
                    [
                        'name' => 'indexes',
                        'in' => 'query',
                        'required' => false,
                        'schema' => ['type' => 'string'],
                        'description' => 'Index to search in (comma separated)'
                    ]
                ]
            ],
            provider: TntSearchProvider::class
        )
    ],
    normalizationContext: ['groups' => [
        Product::GROUP_FRONT_READ,
        Category::GROUP_FRONT_READ,
        Brand::GROUP_FRONT_READ,
        Content::GROUP_FRONT_READ,
        Customer::GROUP_FRONT_READ_SINGLE,
        Folder::GROUP_FRONT_READ,
        Order::GROUP_FRONT_READ
    ]],
)]
class TntSearch
{
    public const GROUP_GLOBAL_TNT_FRONT_READ = [
        'product' => Product::GROUP_FRONT_READ,
        'category' => Category::GROUP_FRONT_READ,
        'brand' => Brand::GROUP_FRONT_READ,
        'content' => Content::GROUP_FRONT_READ,
        'customer' => Customer::GROUP_FRONT_READ_SINGLE,
        'folder' => Folder::GROUP_FRONT_READ,
        'order' => Order::GROUP_FRONT_READ
    ];
}
