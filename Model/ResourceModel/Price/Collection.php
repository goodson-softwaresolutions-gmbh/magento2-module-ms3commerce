<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel\Price;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Staempfli\CommerceImport\Model\Price\TierPrice;
use Staempfli\CommerceImport\Model\ResourceModel\AbstractAttributeCollection;

/**
 * Class Collection
 * @package Staempfli\commerceImport\Model\ResourceModel\Price
 */
class Collection extends AbstractAttributeCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Staempfli\CommerceImport\Model\Reader\Price',
            'Staempfli\CommerceImport\Model\ResourceModel\Price'
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
        $this->setTierPriceAttributesFilter();
    }

    /**
     * @return $this
     */
    protected function setTierPriceAttributesFilter()
    {
        $this->addFieldToFilter('code', [
            ['like' => sprintf('%s%%', $this->tierPrice->getPricePrefix())],
            ['like' => sprintf('%s%%', $this->tierPrice->getQtyPrefix())]
        ]);
        return $this;
    }
}
