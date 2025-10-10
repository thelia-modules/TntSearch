<?php

namespace TntSearch\Index;

class Customer extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return false;
    }

    public function getTokenizer(): string
    {
        return \TntSearch\Tokenizer\CustomerTokenizer::class;
    }

    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        $query = '
            SELECT customer.id, 
            customer.ref, 
            customer.firstname,                    
            customer.lastname, 
            customer.email, 
            CONCAT(address.address1, address.address2, address.address3),            
            address.zipcode, 
            address.city  
            FROM customer
            JOIN address ON address.customer_id=customer.id
        ';

        if ($itemId) {
            $query .= ' WHERE customer.id=' . $itemId;
        }

        return $query;
    }
}