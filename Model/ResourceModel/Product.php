<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel;

use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\AbstractReader;
use Staempfli\CommerceImport\Model\Reader\AttributeFactory;
use Staempfli\CommerceImport\Model\Reader\CategoryFactory;
use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

/**
 * Class Product
 * @package Staempfli\CommerceImport\Model\ResourceModel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends AbstractDb
{
    /**
     * @var string
     */
    protected $rootCategory = null;
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
    protected $productAttributes = [];
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;
    /**
     * @var \Staempfli\CommerceImport\Model\ResourceModel\Category\Collection
     */
    protected $categoryCollection;
    /**
     * @var array
     */
    protected $productCategories = [];
    /**
     * @var array
     */
    protected $categoriesByLevel = [];
    /**
     * @var array
     */
    protected $categoriesById = [];
    /**
     * @var array
     */
    protected $paths = [];
    /**
     * @var Config
     */
    protected $config;

    /**
     * Product constructor.
     * @param Config $config
     * @param AttributeFactory $attributeFactory
     * @param CategoryFactory $categoryFactory
     * @param Context $context
     * @param string $connectionName
     */
    public function __construct(
        Config $config,
        AttributeFactory $attributeFactory,
        CategoryFactory $categoryFactory,
        Context $context,
        $connectionName = CommerceImportSetupConfig::DB_CONNECTION_SETUP
    ) {
        parent::__construct($context, $connectionName);
        $this->attributeFactory = $attributeFactory;
        $this->categoryFactory = $categoryFactory;
        $this->config = $config;
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('m2m_product', 'id');
    }

    /**
     * @param $productId
     * @return mixed|null
     */
    public function getProductAttributes($productId, $marketId = null, $langId = null)
    {
        $productAttributes = [];
        foreach ($this->getAttributeCollection($marketId, $langId) as $attribute) {
            $attributeValues = $attribute->getAttributeValues();
            $value = $attributeValues[$productId]??null;
            if ((int) $attribute->getFieldType() === AbstractReader::ATTRIBUTE_TYPE_BOOLEAN) {
                if ((boolean) $value) {
                    $value = __('Yes');
                } else {
                    $value = __('No');
                }
            }
            $productAttributes[$attribute->getCode()] = $value;
        }

        return $productAttributes;
    }

    /**
     * @param $productId
     * @param $rootCategory
     * @return array|mixed
     */
    public function getProductCategoryPaths($productId, $rootCategory)
    {
        $this->setRootCategory($rootCategory);

        if (!$this->productCategories) {
            foreach ($this->getCategoryCollection() as $item) {
                if ($item->getProductIds()) {
                    foreach ($item->getProductIds() as $row) {
                        $this->productCategories[$row['product_id']][] = $this->getCategoryPath($item->getId());
                    }
                }
            }
        }

        if (isset($this->productCategories[$productId])) {
            return $this->productCategories[$productId];
        }
        return [];
    }

    /**
     * @param int $categoryId
     * @return string
     */
    protected function getCategoryPath($categoryId = 0)
    {
        $this->paths = [];
        $category = $this->getCategoryById($categoryId);
        if ($category) {
            $this->paths[] = $category['name'];
            $this->getParentCategory($category['parent'], $category['level']);
            krsort($this->paths);
            if ($this->config->isCategorySkipActive()) {
                $this->paths = array_slice($this->paths, 0, ($this->config->getCategorySkipLevel() + 1));
            }
            return implode('/', $this->paths);
        }
        return '';
    }

    /**
     * @param $parent
     * @param int $level
     */
    protected function getParentCategory($parent, $level = 0)
    {
        --$level;
        if ($level > 0) {
            $category = $this->getCategoryByLevel($level);
            if ($category && isset($category[$parent])) {
                $this->paths[] = $category[$parent]['name'];
                $this->getParentCategory($category[$parent]['parent'], $category[$parent]['level']);
            }
        } else {
            $this->paths[] = $this->getRootCategory();
        }
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
        $collection->addFieldToFilter('entity_type', AbstractReader::ATTRIBUTE_ENTITY_PRODUCT);
        if ($marketId) {
            $collection->addFieldToFilter('market_id', $marketId);
        }
        if ($langId) {
            $collection->addFieldToFilter('lang_id', $langId);
        }
        $collection->addAttributeValues();
        return $collection;
    }

    /**
     * @return Category\Collection
     */
    public function getCategoryCollection()
    {
        if (!$this->categoryCollection) {
            /** @var $collection \Staempfli\CommerceImport\Model\ResourceModel\Category\Collection */
            $collection = $this->categoryFactory->create()
                ->getCollection()
                ->setOrder('main_table.level', 'asc')
                ->load();
            $collection->addProductIds();
            $this->categoryCollection = $collection;
        }
        return $this->categoryCollection;
    }

    /**
     * @param $categoryId
     * @return bool|mixed
     */
    protected function getCategoryById($categoryId)
    {
        if (!$this->categoriesById) {
            foreach ($this->getCategoryCollection() as $item) {
                $this->categoriesById[$item->getId()] = $item->getData();
            }
        }
        return $this->categoriesById[$categoryId]??false;
    }

    /**
     * @param $level
     * @return bool|mixed
     */
    protected function getCategoryByLevel($level)
    {
        if (!$this->categoriesByLevel) {
            foreach ($this->getCategoryCollection() as $item) {
                $this->categoriesByLevel[$item->getLevel()][$item->getId()] = $item->getData();
            }
        }
        return $this->categoriesByLevel[$level]??false;
    }

    /**
     * @return string
     */
    public function getRootCategory()
    {
        return $this->rootCategory;
    }

    /**
     * @param string $rootCategory
     */
    public function setRootCategory($rootCategory)
    {
        $this->rootCategory = $rootCategory;
    }
}
