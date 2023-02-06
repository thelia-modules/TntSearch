<?php

namespace TntSearch\Event;

class CustomerIndexationEvent extends IndexesEvent
{
    const CUSTOMER_SQL_QUERY_INDEXATION = 'action.tntsearch.customer.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $this->sqlQuery = '
            SELECT customer.id, 
            customer.ref, 
            customer.firstname,                    
            customer.lastname, 
            customer.email, 
            CONCAT(address.address1, address.address2, address.address3),            
            address.zipcode, 
            address.city  
            FROM customer
            JOIN address ON address.customer_id=customer.id;
        ';
    }
}