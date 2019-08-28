<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils\Entity;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\WebsiteFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Api\Data\StoreInterface;
use Staempfli\CommerceImport\Model\Utils\StoreFactory;

/**
 * Class Product
 * @package Staempfli\CommerceImport\Model\Entity
 */
class Product
{
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepositoryInterface;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;
    /**
     * @var StoreFactory
     */
    private $storeFactory;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    public function __construct(
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        WebsiteFactory $websiteFactory,
        StoreFactory $storeFactory,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->productFactory = $productFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->websiteFactory = $websiteFactory;
        $this->storeFactory = $storeFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getImportedProducts()
    {
        $list = $this->productRepositoryInterface->getList(
            $this->searchCriteriaBuilder->addFilter('ms3_imported', 1)->create()
        );

        return $list->getItems();
    }

    /**
     * @param string $sku
     * @return bool
     */
    public function isProductSkuImported(string $sku)
    {
        $list = $this->productRepositoryInterface->getList(
            $this->searchCriteriaBuilder
                ->addFilter('ms3_imported', 1)
                ->addFilter('sku', $sku)
                ->create()
        );

        if ($list->getItems()) {
            return true;
        }

        return false;
    }

    /**
     * IMPORTANT:
     * - Here we cannot use productRepositoryInterface->getList because this method does not allow to specify a store
     * http://magento.stackexchange.com/questions/91273/magento-2-how-to-filter-a-product-collection-by-store-id/106135
     * @param StoreInterface $store
     * @return \Magento\Catalog\Model\Product[]
     */
    public function getInactiveProducts($store)
    {
        $productCollection = $this->productCollectionFactory->create();
        /**
         * IMPORTANT: Specifically set storeId checking Master,
         * otherwise the scope will not match with the scope of the data imported
         * - Not setting the right Store turns out into all products being detached from current website on
         * detachInactiveProductsFromWebsite()
         */
        $productCollection->setStoreId($store->getId())
            ->addFieldToFilter('ms3_active', 0);

        return $productCollection->getItems();
    }

    /**
     * @param array $productIds
     * @return mixed
     */
    public function getProductWebsitesByProductIds(array $productIds)
    {
        return $this->websiteFactory->create()->getWebsites($productIds);
    }

    /**
     * @param array $websiteIds
     * @param array $productIds
     * @return mixed
     */
    public function removeWebsitesFromProducts(array $websiteIds, array $productIds)
    {
        return $this->websiteFactory->create()->removeProducts($websiteIds, $productIds);
    }
}
