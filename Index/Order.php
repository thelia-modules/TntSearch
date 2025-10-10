<?php

namespace TntSearch\Index;

class Order extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return false;
    }

    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        $query = '
            SELECT `order`.id AS id,
            `order`.id AS order_id,
            `order`.ref AS ref,
            customer.ref AS customer_ref,
            customer.firstname AS firstname,
            customer.lastname AS lastname,
            customer.email AS email,
            `order`.invoice_ref AS invoice_ref,
            `order`.transaction_ref AS transaction_ref,
            `order`.delivery_ref AS delivery_ref
            FROM `order` 
            LEFT JOIN customer ON `order`.customer_id = customer.id
        ';

        if ($itemId) {
            $query .= ' WHERE `order`.id =' . $itemId;
        }

        return $query;
    }
}