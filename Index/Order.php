<?php

namespace TntSearch\Index;

class Order extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return false;
    }

    /**
     * @param int|null $itemId
     * @param string|null $locale
     * @return string
     */
    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        return '
            SELECT `order`.id AS id,
            `order`.ref AS ref,
            customer.ref AS customer_ref,
            customer.firstname AS firstname,
            customer.lastname AS lastname,
            customer.email AS email,
            `order`.invoice_ref AS invoice_ref,
            `order`.transaction_ref AS transaction_ref,
            `order`.delivery_ref AS delivery_ref
            FROM `order` 
            LEFT JOIN customer ON `order`.customer_id = customer.id;
        ';
    }
}