<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel\Attribute;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Staempfli\CommerceImport\Model\AbstractReader;
use Staempfli\CommerceImport\Model\Price\TierPrice;
use Staempfli\CommerceImport\Model\ResourceModel\AbstractAttributeCollection;

/**
 * Class Collection
 * @package Staempfli\commerceImport\Model\ResourceModel\Attribute
 */
class Collection extends AbstractAttributeCollection
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Staempfli\CommerceImport\Model\Reader\Attribute',
            'Staempfli\CommerceImport\Model\ResourceModel\Attribute'
        );
    }

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
            $tierPrice,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $metadataPool,
            $connection,
            $resource
        );
        $this->ignoreTierPriceAttributes();
    }

    /**
     * @return $this
     */
    protected function ignoreTierPriceAttributes()
    {
        $this
            ->addFieldToFilter('code', ['nlike' => sprintf('%s%%', $this->tierPrice->getPricePrefix())])
            ->addFieldToFilter('code', ['nlike' => sprintf('%s%%', $this->tierPrice->getQtyPrefix())]);
        return $this;
    }

    /**
     * @return $this
     */
    public function addAttributeSets()
    {
        $this->load();

        $codes = [];
        $codeGroups = [];
        if ($this->getFlag('attribute_sets_added')) {
            return $this;
        }

        $ids = array_keys($this->_items);
        if (empty($ids)) {
            return $this;
        }

        foreach ($this->_items as $item) {
            if ($item->getEntityType() != AbstractReader::ATTRIBUTE_ENTITY_PRODUCT) {
                continue;
            }
            $codes[$item->getCode()] = [];
            $codeGroups[$item->getCode()] = $item->getGroup();
        }

        $query = $this->getQueryFromDatabase(
            'm2m_attribute_set',
            'attribute_code',
            ['id', 'name', 'LOWER(attribute_code) as attribute_code', 'sort'],
            array_keys($codes)
        );

        while ($row = $query->fetch()) {
            $codes[$row['attribute_code']][$row['id']] = [
                'attribute_set_code' => $row['name'],
                'group' => $codeGroups[$row['attribute_code']],
                'position' => $row['sort'],
            ];
        }

        foreach ($this->_items as $item) {
            if (isset($codes[$item->getCode()])) {
                $item->setAttributeSets($codes[$item->getCode()]);
            } else {
                $item->setAttributeSets([]);
            }
        }

        $this->setFlag('attribute_sets_added', true);
        return $this;
    }

    /**
     * @return $this
     */
    public function addAttributeOptions()
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

        $query = $this->getQueryFromDatabase(
            'm2m_attribute_option',
            'attribute_id',
            ['id', 'attribute_id', 'option_id', 'option_value', 'sort'],
            $ids
        );

        while ($row = $query->fetch()) {
            $data[$row['attribute_id']][$row['option_id']] = [
                'value' => $row['option_value'],
                'sort_order' => $row['sort']
            ];
        }

        foreach ($this->_items as $item) {
            if (isset($data[$item->getId()])) {
                $item->setAttributeOptions($data[$item->getId()]);
            } else {
                $item->setAttributeOptions(null);
            }
        }
        $this->setFlag('attribute_values_added', true);
        return $this;
    }

    private function getQueryFromDatabase(
        string $table,
        string $column,
        array $fields,
        array $in
    ): \Zend_Db_Statement_Interface {
        $select = $this->getConnection()->select();
        $select->from($this->getResource()->getTable($table), $fields);
        $select->where(sprintf('%s IN (?)', $column), $in);
        return $this->getConnection()->query($select);
    }
}
