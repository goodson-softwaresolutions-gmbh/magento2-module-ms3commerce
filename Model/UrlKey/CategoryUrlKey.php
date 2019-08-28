<?php
/**
 * CategoryUrlKey
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\UrlKey;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filter\FilterManager;
use Staempfli\CommerceImport\Model\Utils\StoreFactory;

class CategoryUrlKey extends AbstractUrlKey
{
    /**
     * @var StoreFactory
     */
    private $storeFactory;

    public function __construct(
        StoreFactory $storeFactory,
        FilterManager $filterManager,
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig
    ) {
        parent::__construct($filterManager, $resourceConnection, $eavConfig);
        $this->storeFactory = $storeFactory;
    }

    protected function getEntityType()
    {
        return Category::ENTITY;
    }

    protected function getUniqueIdentifiersAndIdPairs(): array
    {
        $attributeId = $this->getAttributeId($this->getEntityType(), 'ms3_guid');
        $varcharTable = $this->resourceConnection->getTableName(sprintf('%s_entity_varchar', $this->getEntityType()));
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from(['eav_varchar' => $varcharTable], ['value', 'entity_id'])
            ->where('eav_varchar.attribute_id = ?', $attributeId)
            ->where('eav_varchar.store_id = ?', $this->getStoreId());
        return $connection->fetchPairs($query);
    }

    protected function getStoreId() : int
    {
        /** @var \Staempfli\CommerceImport\Model\Utils\Store $storeUtil */
        $storeUtil = $this->storeFactory->create();
        return $storeUtil->getCurrentStore()->getId();
    }
}
