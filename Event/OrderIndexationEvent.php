<?php

namespace TntSearch\Event;

class OrderIndexationEvent extends IndexesEvent
{
    const ORDER_SQL_QUERY_INDEXATION = 'action.tntsearch.order.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $this->sqlQuery = '
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