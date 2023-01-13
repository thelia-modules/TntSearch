<?php

namespace TntSearch\Event;

class ProductIndexationEvent extends IndexesEvent
{
    const PRODUCT_SQL_QUERY_INDEXATION = 'action.tntsearch.product.sql.query.indexation';

    public function buildSqlQuery():void
    {
        $locale = $this->getLocale();

        $this->sqlQuery = 'SELECT product.id AS id, 
        product.ref AS ref,
        pse.ref AS pse_ref,
        GROUP_CONCAT(DISTINCT(pse.ean_code)) AS ean_codes,
        GROUP_CONCAT(DISTINCT(fai.title)) AS features,
        GROUP_CONCAT(DISTINCT(aavi.title)) AS attributes,
        pi.title AS title, 
        pi.chapo AS chapo, 
        pi.description AS description, 
        pi.postscriptum AS postscriptum,
        bi.title AS brand
        FROM product
        LEFT JOIN product_i18n AS pi ON product.id = pi.id 
        LEFT JOIN product_sale_elements AS pse ON product.id = pse.product_id
        LEFT JOIN brand AS b ON b.id = product.brand_id
        LEFT JOIN brand_i18n AS bi ON bi.id = b.id AND bi.locale=\'' . $locale . '\' 
        LEFT JOIN feature_product AS fp ON product.id = fp.product_id
        LEFT JOIN feature_av AS fa ON fp.`feature_av_id` = fa.id
        LEFT JOIN feature_av_i18n AS fai ON fa.id = fai.id AND fai.locale=\'' . $locale . '\'         
        LEFT JOIN attribute_combination AS ac ON pse.id = ac.`product_sale_elements_id`
        LEFT JOIN attribute_av AS aav ON aav.id = ac.`attribute_av_id`
        LEFT JOIN attribute_av_i18n AS aavi ON aav.id = aavi.id AND aavi.locale=\'' . $locale . '\'

        WHERE pi.locale=\'' . $locale . '\' GROUP BY product.id;';
    }
}