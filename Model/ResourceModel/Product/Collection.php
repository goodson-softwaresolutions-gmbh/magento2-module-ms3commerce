<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel\Product;

use \Magento\Cms\Model\ResourceModel\AbstractCollection;
use Staempfli\CommerceImport\Model\Config;
use \Staempfli\CommerceImport\Setup\ImportDatabaseSetup;

/**
 * Class Product
 * @package Staempfli\CommerceImport\Model\ResourceModel\Category
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends AbstractCollection
{
    const M2M_LINK_TYPE_RELATED = 1;
    const M2M_LINK_TYPE_CROSSELL = 2;
    const M2M_LINK_TYPE_UPSELL = 3;

    /**
     * @var array
     */
    protected $linkTypeMapping = [
        self::M2M_LINK_TYPE_RELATED => '_related_',
        self::M2M_LINK_TYPE_CROSSELL => '_crosssell_',
        self::M2M_LINK_TYPE_UPSELL => '_upsell_',
    ];
    /**
     * @var Config
     */
    private $config;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Staempfli\CommerceImport\Model\Reader\Product',
            'Staempfli\CommerceImport\Model\ResourceModel\Product'
        );
    }

    public function __construct(
        Config $config,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
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
        $this->config = $config;
    }

    /**
     * @param array|int|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true): Collection
    {
        $this->performAddStoreFilter($store, $withAdmin);
        return $this;
    }

    /**
     * Retrieve id - sku pairs
     *
     * @return array
     */
    public function getIdSkuPairs(): array
    {
        $select = clone $this->getSelect();
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);

        $select->columns($this->getResource()->getIdFieldName(), 'main_table');
        $select->columns('sku', 'main_table');
        return $this->getConnection()->fetchPairs($select, $this->_bindParams);
    }

    public function setRelationsConfiguration(): Collection
    {
        $parents = [];
        $children = [];

        $this->load();
        $ids = array_keys($this->_items);
        if (empty($ids)) {
            return $this;
        }

        $select = $this->getConnection()->select();
        $select->from(
            $this->getResource()->getTable(ImportDatabaseSetup::PRODUCT_RELATION_TABLE),
            ['id', 'type', 'parent_id', 'child_id', 'sort']
        )->where('parent_id IN (?)', $ids);
        $query = $this->getConnection()->query($select);

        while ($row = $query->fetch()) {
            $children[] = $row['child_id'];
            $parents[$row['parent_id']]['type'] = (int)$row['type'];
            $parents[$row['parent_id']]['children'][$row['child_id']] = ['sort' => $row['sort']];
        }

        foreach ($this->_items as $item) {
            if (in_array($item->getId(), $children)) {
                $item->setVisibility(__('Not Visible Individually'));
            } elseif (isset($parents[$item->getId()])) {
                $item->setChildren($parents[$item->getId()]['children']);
            }
        }

        return $this;
    }

    public function setLinkedProducts(): Collection
    {
        $this->load();
        $ids = array_keys($this->_items);
        if (empty($ids)) {
            return $this;
        }

        $select = $this->getConnection()->select();
        $select->from(
            ['prod_links' => $this->getResource()->getTable(ImportDatabaseSetup::PRODUCT_LINK_TABLE)],
            [
                'type_id' => 'type',
                'source_id' => 'source_id',
                'destination_ids' => new \Zend_Db_Expr('GROUP_CONCAT(prod_links.destination_id)'),
                'positions' => new \Zend_Db_Expr('GROUP_CONCAT(prod_links.sort)'),
            ]
        )->where('source_id IN (?)', $ids)
        ->group(['type_id', 'source_id']);
        $query = $this->getConnection()->query($select);

        while ($row = $query->fetch()) {
            $this->addLinkedProductsFromRow($row);
        }

        return $this;
    }

    private function addLinkedProductsFromRow(array $row)
    {
        if (!$row['destination_ids'] || !$row['positions']) {
            return;
        }
        $destinations = explode(',', $row['destination_ids']);
        $positions = explode(',', $row['positions']);
        $linkedSkus = [];
        foreach ($destinations as $key => $destinationId) {
            if (isset($this->_items[$destinationId]) && $row['type_id'] < 10) {
                $linkedSkus[] = $this->_items[$destinationId]['sku'];
            } else {
                unset($positions[$key]);
            }
        }
        if ($linkedSkus) {
            $linkedSkus = implode($this->config->getMultipleValuesSeparator(), $linkedSkus);
            $positions = implode($this->config->getMultipleValuesSeparator(), $positions);
            $this->_items[$row['source_id']][$this->linkTypeMapping[$row['type_id']] . 'sku'] = $linkedSkus;
            $this->_items[$row['source_id']][$this->linkTypeMapping[$row['type_id']] . 'position'] = $positions;
        }
    }
}
