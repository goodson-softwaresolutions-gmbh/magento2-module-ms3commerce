<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Staempfli\CommerceImport\Model\Config\Source\CategoryAssignationMode;

class Config
{
    const XML_PATH_BEHAVIOR = 'ms3commerce/default/behavior';
    const XML_PATH_VALIDATION_STRATEGY = 'ms3commerce/default/validation_strategy';
    const XML_PATH_ALLOWED_ERROR_COUNT = 'ms3commerce/default/allowed_error_count';
    const XML_PATH_IMPORT_IMAGES_FILE_FIR = 'ms3commerce/default/import_images_file_dir';
    const XML_PATH_IMPORT_MULTIPLE_VALUES_SEPARATOR = 'ms3commerce/default/multiple_values_separator';
    const XML_PATH_MAPPING_MASTER = 'ms3commerce/mapping/master';
    const XML_PATH_MAPPING_MARKET_ID = 'ms3commerce/mapping/market_id';
    const XML_PATH_MAPPING_LANG_ID = 'ms3commerce/mapping/lang_id';
    const XML_PATH_PRICE_TIER_PRICE_PREFIX = 'ms3commerce/price/tier_price_prefix';
    const XML_PATH_PRICE_TIER_QTY_PREFIX = 'ms3commerce/price/tier_qty_prefix';
    const XML_PATH_READER_IGNORE_INVALID_IMAGES = 'ms3commerce/reader/ignore_invalid_images';
    const XML_PATH_READER_HANDLE_SPECIAL_CHARS = 'ms3commerce/reader/handle_special_chars';
    const XML_PATH_CATEGORY_ASSOCIATION_MODE = 'ms3commerce/category/assignation_mode';
    const XML_PATH_CATEGORY_DELETE_OLD_ASSOCIATIONS = 'ms3commerce/category/delete_old_assignations';
    const XML_PATH_CATEGORY_SKIP = 'ms3commerce/category/skip';
    const XML_PATH_CATEGORY_LEVEL = 'ms3commerce/category/level';
    const XML_PATH_ATTRIBUTE_SORT_ORDER = 'ms3commerce/attribute/keep_magento_sort_order';
    const XML_PATH_PRODUCT_SKU_TO_URL_KEY_CHILD = 'ms3commerce/product/add_sku_to_child_url_key';
    const XML_PATH_PRODUCT_SKU_TO_URL_KEY_PARENT = 'ms3commerce/product/add_sku_to_parent_url_key';
    /**
     * @var ValueFactory
     */
    private $valueFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ValueFactory $valueFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ValueFactory $valueFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->valueFactory = $valueFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getBehavior()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BEHAVIOR,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getValidationStrategy()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_VALIDATION_STRATEGY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getAllowedErrorCount()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_ERROR_COUNT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getImportFileDir()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_IMPORT_IMAGES_FILE_FIR,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getMultipleValuesSeparator()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_IMPORT_MULTIPLE_VALUES_SEPARATOR,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getMarketId()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MAPPING_MARKET_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getLangId()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MAPPING_LANG_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isCategorySkipActive()
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_SKIP,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getCategorySkipLevel()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_LEVEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getSpecialCharsHandling()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_READER_HANDLE_SPECIAL_CHARS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isIgnoreInvalidImages()
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_READER_IGNORE_INVALID_IMAGES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return array
     */
    public function getMappingConfiguration()
    {
        $data = [];
        /** @var $value \Magento\Framework\App\Config\Value */
        $value = $this->valueFactory->create();
        /** @var $collection \Magento\Config\Model\ResourceModel\Config\Data\Collection */
        $collection = $value->getCollection()->addFieldToFilter('path', ['like' => 'ms3commerce/mapping/%']);
        $items = $collection->getItems();

        foreach ($items as $item) {
            if ($item->getPath() === self::XML_PATH_MAPPING_MARKET_ID) {
                $data[$item->getScopeId()]['market_id'] = $item->getValue();
            }
            if ($item->getPath() === self::XML_PATH_MAPPING_LANG_ID) {
                $data[$item->getScopeId()]['lang_id'] = $item->getValue();
            }
        }
        return $data;
    }

    /**
     * @param $store
     * @return mixed
     */
    public function getStructureMaster($store)
    {
        $result = $this->scopeConfig->getValue(
            self::XML_PATH_MAPPING_MASTER,
            ScopeInterface::SCOPE_WEBSITES,
            $store->getWebsite()->getCode()
        );
        return $result;
    }

    /**
     * @param $store
     * @return mixed
     */
    public function getPrimaryStructureMaster()
    {
        return $this->storeManager->getDefaultStoreView();
    }

    /**
     * @param $store
     * @return bool
     */
    public function isStructureMaster($store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore();
        }
        $master = $this->getStructureMaster($store);
        if (null != $master && (int)$master === (int)$store->getStoreId()) {
            return true;
        }
        return false;
    }

    /**
     * @param $store
     * @return bool
     */
    public function isPrimaryStructureMaster($store = null)
    {
        if (!$store) {
            $store = $this->storeManager->getStore();
        }
        $master = $this->getPrimaryStructureMaster();
        if (null != $master && (int)$master->getId() === (int)$store->getStoreId()) {
            return true;
        }
        return false;
    }

    public function categoryAssignationMode()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CATEGORY_ASSOCIATION_MODE);
    }

    public function deleteOldCategoryAssignations()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CATEGORY_DELETE_OLD_ASSOCIATIONS);
    }

    public function attributeKeepMagentoSortOrder(): int
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTE_SORT_ORDER);
    }

    public function canAddSkuToChildProductUrlKey(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PRODUCT_SKU_TO_URL_KEY_CHILD);
    }

    public function canAddSkuToParentProductUrlKey(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PRODUCT_SKU_TO_URL_KEY_PARENT);
    }
}
