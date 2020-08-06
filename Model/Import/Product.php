<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Import;

use Magento\Catalog\Model\Product as MagentoModelProduct;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\App\ResourceConnection as ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Staempfli\CommerceImport\Api\Data\ProductImportInterface;
use Staempfli\CommerceImport\Model\Utils\Import as ImportUtils;
use Staempfli\CommerceImport\Model\Reader\Product as ProductReader;
use Staempfli\CommerceImport\Model\AbstractImport;
use Staempfli\CommerceImport\Model\Import\Processor\ProductImportProcessor;

class Product extends AbstractImport implements ProductImportInterface
{
    /**
     * @var array
     */
    protected $products = [];
    /**
     * @var array
     */
    protected $existingProducts = [];
    /**
     * @var array
     */
    protected $onlyProductSkus = [];
    /**
     * @var ProductReader
     */
    private $productReader;
    /**
     * @var ProductImportProcessor
     */
    private $importProcessor;
    /**
     * @var ImportUtils
     */
    private $importUtils;
    /**
     * @var StoreInterface[]
     */
    private $stores;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    public function __construct(
        ProductReader $productReader,
        ProductImportProcessor $importProcessor,
        ImportUtils $importUtils,
        ResourceConnection $resourceConnection,
        AttributeRepository $attributeRepository
    ) {
        parent::__construct($importUtils);
        $this->productReader = $productReader;
        $this->importProcessor = $importProcessor;
        $this->importUtils = $importUtils;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
    }

    public function setOnlyProductSkus(string $onlyProductSkus)
    {
        if ($onlyProductSkus) {
            $this->onlyProductSkus = explode(',', $onlyProductSkus);
        } else {
            $this->onlyProductSkus = [];
        }
    }

    /**
     * Prepare and setData to be imported
     *
     * @return void
     */
    public function prepare()
    {
        if ($this->onlyProductSkus) {
            $this->productReader->setProductSkusFilter($this->onlyProductSkus);
        }

        $this->products = $this->productReader->fetch();
        $this->importProcessor->addImportData($this->products);
    }

    /**
     * Validate data and set Data in Table that will be imported in next step
     *
     * @throws \Exception
     */
    public function validate()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Validate products');
        if ($this->importProcessor->validateAndSetDataInTable()) {
            $this->getImportUtils()
                ->getConsoleOutput()
                ->info(sprintf('%d valid products', count($this->importProcessor->getImportData())));
        } else {
            throw new \Exception($this->importProcessor->getLogTrace());
        }
    }

    /**
     * @param StoreInterface[] $stores
     */
    public function setStores($stores)
    {
        $this->stores = $stores;
    }

    /**
     * Import products
     */
    public function import()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Import products');
        $this->markProductsAsInactive();
        $this->getImportUtils()
            ->getEventManager()
            ->dispatch('import_products_before', ['products' => $this->products]);
        // If this is needed by project, make a import_products_before observer instead:
        //$this->getImportUtils()->getProductMediaUtils()->deleteExistingMediaFiles($this->products);
        if (!$this->importProcessor->processImport()) {
            throw new \Exception($this->importProcessor->getLogTrace());
        }
        $this->getImportUtils()->getConsoleOutput()->info($this->importProcessor->getLogTrace());
        $this->getImportUtils()->getConsoleOutput()->title('Update media values');
        foreach ($this->stores as $store) {
            $this->getImportUtils()->getProductMediaUtils()->updateMediaValues($this->products, $store);
        }

        $this->getImportUtils()->getConsoleOutput()->info('done');

        $this->getImportUtils()->getEventManager()->dispatch('import_products_after', ['products' => $this->products]);
        $this->detachInactiveProductsFromWebsite();
    }

    /**
     * Clean up products
     */
    public function clearMemory()
    {
        $this->products = [];
        $this->existingProducts = [];
        $this->productReader->cleanModelCache();
    }

    /**
     * Mark products as inactive before import starts
     */
    protected function markProductsAsInactive()
    {
        foreach ($this->stores as $store) {
            $storeId = $store->getId();
            if ($this->getImportUtils()->getConfig()->isPrimaryStructureMaster($store)) {
                $this->deleteStoreSpecificValues('ms3_active', $storeId);
                $storeId = 0;
            }
            $products = $this->getImportUtils()->getProductUtils()->getImportedProducts();
            foreach ($products as $product) {
                $product->setStoreId($storeId)->setData('ms3_active', 0)->getResource()->saveAttribute(
                    $product,
                    'ms3_active'
                );
            }
        }
    }

    /**
     * Disable inactive products once the import finishes
     */
    protected function detachInactiveProductsFromWebsite()
    {
        foreach ($this->stores as $store) {
            $products = $this->getImportUtils()->getProductUtils()->getInactiveProducts($store);
            if (count($products)) {
                $this->getImportUtils()->getProductUtils()->removeWebsitesFromProducts(
                    [$store->getWebsiteId()],
                    array_keys($products)
                );
            }
        }
    }

    public function setMarketAndLangId($marketId, $langId)
    {
        $this->productReader->setMarketAndLangId($marketId, $langId);
    }

    private function deleteStoreSpecificValues($attributeCode, $storeId)
    {
        $attribute = $this->attributeRepository->get($attributeCode);
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            $connection->getTableName('catalog_product_entity_int'),
            [
                'attribute_id = ?' => $attribute->getAttributeId(),
                'store_id = ?' => $storeId
            ]
        );
    }
}
