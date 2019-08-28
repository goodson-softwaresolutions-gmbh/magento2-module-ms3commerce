<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel;

use Magento\Cms\Model\ResourceModel\AbstractCollection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Staempfli\CommerceImport\Model\Price\TierPrice;

/**
 * Class Collection
 * @package Staempfli\commerceImport\Model\ResourceModel\Attribute
 */
abstract class AbstractAttributeCollection extends AbstractCollection
{
    /**
     * @var TierPrice
     */
    protected $tierPrice;

    /**
     * Collection constructor.
     * @param TierPrice $tierPrice
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        TierPrice $tierPrice,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        MetadataPool $metadataPool,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $metadataPool,
            $connection,
            $resource
        );
        $this->tierPrice = $tierPrice;
    }

    protected function _initSelect() //@codingStandardsIgnoreLine
    {
        $this->addFieldToSelect(new \Zend_Db_Expr('LOWER(code)'), 'code');
        parent::_initSelect();
    }

    /**
     * @param array|int|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        $this->performAddStoreFilter($store, $withAdmin);
        return $this;
    }

    /**
     * @return $this
     */
    public function addAttributeValues()
    {
        $this->load();

        $data = [];
        if ($this->getFlag('attribute_values_added')) {
            return $this;
        }
        $ids = array_keys($this->_items);
        if (empty($ids)) {
            return $this;
        }

        $valuesQuery = $this->getAttributeValuesQuery($ids);
        while ($row = $valuesQuery->fetch()) {
            $data[$row['attribute_id']][$row['resource_id']] = $row['value'];
        }

        foreach ($this->_items as $item) {
            if (isset($data[$item->getId()])) {
                $item->setAttributeValues($data[$item->getId()]);
            } else {
                $item->setAttributeValues([]);
            }
        }
        $this->setFlag('attribute_values_added', true);
        return $this;
    }

    private function getAttributeValuesQuery(array $ids): \Zend_Db_Statement_Interface
    {
        $columns = [
            'attribute_id',
            'resource_id' => new \Zend_Db_Expr('IF(product_id <> 0, product_id, category_id)'),
            'value' => new \Zend_Db_Expr(
                sprintf(
                    'GROUP_CONCAT(value SEPARATOR "%s")',
                    \Magento\CatalogImportExport\Model\Import\Product::PSEUDO_MULTI_LINE_SEPARATOR
                )
            ),
        ];
        /** @var \Magento\Framework\DB\Select $select */
        $select = $this->getConnection()->select();
        $select->from($this->getResource()->getTable('m2m_attribute_value'), $columns);
        $select->where('attribute_id IN (?)', $ids);
        $select->group(['attribute_id', 'product_id', 'category_id']);
        return $this->getConnection()->query($select);
    }
}
