<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */

namespace Staempfli\CommerceImport\Model\Utils;

use Magento\Catalog\Model\Category;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store as StoreModel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Staempfli\CommerceImport\Model\Config;

/**
 * Class Store
 * @package Staempfli\CommerceImport\Model
 */
class Store
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Store constructor.
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        Config $config,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param int $id
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStoreById(int $id)
    {
        return $this->storeRepository->getById($id);
    }

    /**
     * Get StoreId depending whether store is master
     *
     * - This is needed on some action to match the scope of the data imported
     *
     * @return int
     */
    public function getStoreIdCheckingMaster()
    {
        $currentStore = $this->getCurrentStore();
        $storeId = $currentStore->getId();

        if ($this->config->isStructureMaster($currentStore)) {
            $storeId = StoreModel::DEFAULT_STORE_ID;
        }

        return $storeId;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Get current website Id where the products are imported
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getWebsite()->getId();
    }

    /**
     * @param string|int $marketId
     * @param string|int $langId
     * @return bool|\Magento\Store\Api\Data\StoreInterface
     */
    public function getStoreByMarketAndLang(string $marketId, string $langId)
    {
        $config = $this->config->getMappingConfiguration();

        foreach ($config as $storeId => $row) {
            if ((int) $row['market_id'] === (int) $marketId
                && (int) $row['lang_id'] === (int) $langId
            ) {
                return $this->getStoreById((int)$storeId);
            }
        }
        return false;
    }

    /**
     * @param int|null $storeId
     * @return Category
     */
    public function getImportRootCategory($storeId = null): Category
    {
        $store = $this->storeManager->getStore($storeId);
        $rootCategoryId = $store->getRootCategoryId();
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->setStoreId($store->getId());
        $categoryCollection->addAttributeToSelect('name');
        $categoryCollection->addAttributeToFilter('ms3_import_category', 1);
        $categoryCollection->addAttributeToFilter('path', ['like' => '%/' . $rootCategoryId . '/%']);


        // If there is no category with the flag ms3_import_category set
        // use default root category
        if ($categoryCollection->getSize() !== 1) {
            $categoryId = $store->getGroup()->getRootCategoryId();
            $categoryCollection = $this->categoryCollectionFactory->create()
                ->setStoreId($store->getId())
                ->addAttributeToSelect('name')
                ->addFieldToFilter('entity_id', $categoryId);
        }

        return $categoryCollection->getFirstItem();
    }
}
