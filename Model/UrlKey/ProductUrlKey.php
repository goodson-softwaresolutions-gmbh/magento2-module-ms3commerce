<?php
/**
 * ProductUrlKey
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\UrlKey;

use Magento\Catalog\Model\Product;

class ProductUrlKey extends AbstractUrlKey
{
    protected function getEntityType()
    {
        return Product::ENTITY;
    }

    protected function getUniqueIdentifiersAndIdPairs(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from('catalog_product_entity', ['sku', 'entity_id']);
        return $connection->fetchPairs($query);
    }
}
