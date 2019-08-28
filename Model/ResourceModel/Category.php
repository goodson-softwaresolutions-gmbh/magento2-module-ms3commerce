<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Staempfli\CommerceImport\Model\ResourceModel\Attribute\Collection;
use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;
use Staempfli\CommerceImport\Model\Reader\AttributeFactory;
use Staempfli\CommerceImport\Model\AbstractReader;

/**
 * Class Category
 * @package Staempfli\CommerceImport\Model\ResourceModel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Category extends AbstractDb
{
    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;
    /**
     * @var \Staempfli\CommerceImport\Model\ResourceModel\Attribute\Collection
     */
    protected $attributeCollection;
    /**
     * @var array
     */
    protected $categoryAttributes = [];

    public function __construct(
        AttributeFactory $attributeFactory,
        Context $context,
        $connectionName = CommerceImportSetupConfig::DB_CONNECTION_SETUP
    ) {
        parent::__construct($context, $connectionName);
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('m2m_category', 'id');
    }

    public function getCategoryAttributes(int $categoryId, $marketId = null, $langId = null)
    {
        $categoryAttributes = [];
        foreach ($this->getAttributeCollection($marketId, $langId) as $attribute) {
            $attributeValues = $attribute->getAttributeValues();
            $value = $attributeValues[$categoryId]??null;
            if ((int) $attribute->getFieldType() === AbstractReader::ATTRIBUTE_TYPE_BOOLEAN) {
                $value = (boolean) $value;
            }
            $categoryAttributes[$attribute->getCode()] = $value;
        }

        return $categoryAttributes;
    }

    /**
     * @return bool
     */
    public function hasCategoryAttributes()
    {
        return (bool) $this->getAttributeCollection()->getSize();
    }

    /**
     * @param int|null $marketId
     * @param int|null $langId
     * @return Attribute\Collection
     */
    protected function getAttributeCollection($marketId = null, $langId = null)
    {
        /** @var $collection \Staempfli\CommerceImport\Model\ResourceModel\Attribute\Collection */
        $collection = $this->attributeFactory->create()->getCollection();
        $collection->addFieldToFilter('entity_type', AbstractReader::ATTRIBUTE_ENTITY_CATEGORY);
        if ($marketId) {
            $collection->addFieldToFilter('market_id', $marketId);
        }
        if ($langId) {
            $collection->addFieldToFilter('lang_id', $langId);
        }
        $collection->addAttributeValues();
        return $collection;
    }

}
