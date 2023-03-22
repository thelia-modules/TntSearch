<?php

namespace TntSearch\Index;

class Customer extends BaseIndex
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
            SELECT customer.id, 
            customer.ref, 
            customer.firstname,                    
            customer.lastname, 
            customer.email, 
            customer.email as email2,
            CONCAT(address.address1, address.address2, address.address3),            
            address.zipcode, 
            address.city  
            FROM customer
            JOIN address ON address.customer_id=customer.id;
        ';
    }
}