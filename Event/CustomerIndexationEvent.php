<?php

namespace TntSearch\Event;

class CustomerIndexationEvent extends IndexesEvent
{
    const CUSTOMER_SQL_QUERY_INDEXATION = 'action.tntsearch.customer.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $this->sqlQuery = 'SELECT id, ref, firstname, lastname, email FROM customer;';
    }
}