<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Reader;

use Magento\CatalogImportExport\Model\Import\Product as ProductImport;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProductImport;
use Magento\CatalogImportExport\Model\Import\Product\Validator\Media;
use Magento\Framework\Data\Collection\AbstractDb as MagentoAbstractDb;
use Magento\Store\Model\Store;
use Staempfli\CommerceImport\Api\Data\ProductReaderInterface;
use Staempfli\CommerceImport\Logger\CommerceImportLogger;
use Staempfli\CommerceImport\Model\AbstractReader;
use Staempfli\CommerceImport\Model\Config\Source\CategoryAssignationMode;
use Staempfli\CommerceImport\Model\Reader\Product\TypeFactory;
use Staempfli\CommerceImport\Model\ResourceModel\Category\CategoryAssignationResource;
use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;
use Staempfli\CommerceImport\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\CatalogImportExport\Model\Export\Product as MagentoProductExport;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Staempfli\CommerceImport\Model\ResourceModel\Product\CollectionFactory as ImportProductCollectionFactory;
use Staempfli\CommerceImport\Model\UrlRewriteCleaner;
use Staempfli\CommerceImport\Model\Utils\Reader as ReaderUtils;

/**
 * Class Product
 * @package Staempfli\CommerceImport\Model\Reader
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class Product extends AbstractReader implements ProductReaderInterface
{
    /**
     * @var array
     */
    private $products = [];
    /**
     * @var ProductCollection
     */
    private $productCollection;
    /**
     * @var \Magento\Framework\Filesystem\Directory\Read
     */
    private $rootDirectory;
    /**
     * @var array
     */
    private $defaultProductAttributes = [
        'sku' => null,
        'name' => null,
        'description' => null,
        'short_description' => null,
        'image' => null,
        'weight' => null,
        'meta_title' => null,
        'meta_keywords' => null,
        'meta_description' => null,
        'new_from_date' => null,
        'new_to_date' => null,
        'country_of_manufacture' => null,
        'url_key' => null,
        'price' => '0.0',
        'qty' => 0,
        'categories' => null,
        'is_in_stock' => 1,
    ];
    /**
     * @var TypeFactory
     */
    private $productTypeFactory;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var CommerceImportLogger
     */
    private $commerceImportLogger;
    /**
     * @var CategoryAssignationResource
     */
    private $categoryAssignationResource;
    /**
     * @var UrlRewriteCleaner
     */
    private $urlRewriteCleaner;
    /**
     * @var ImportProductCollectionFactory
     */
    private $importProductCollectionFactory;
    /**
     * @var array
     */
    private $categoriesBySku = [];
    /**
     * @var array
     */
    private $productSkus = [];

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct() // phpcs:ignore
    {
        parent::_construct();
        $this->_init(\Staempfli\CommerceImport\Model\ResourceModel\Product::class);
    }

    /**
     * Product constructor.
     * @param TypeFactory $productTypeFactory
     * @param Filesystem $filesystem
     * @param CommerceImportLogger $commerceImportLogger
     * @param ReaderUtils $readerUtils
     * @param Context $context
     * @param Registry $registry
     * @param CategoryAssignationResource $categoryAssignationResource
     * @param UrlRewriteCleaner $urlRewriteCleaner
     * @param AbstractDb|null $resource
     * @param MagentoAbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TypeFactory $productTypeFactory,
        Filesystem $filesystem,
        CommerceImportLogger $commerceImportLogger,
        ReaderUtils $readerUtils,
        Context $context,
        Registry $registry,
        CategoryAssignationResource $categoryAssignationResource,
        UrlRewriteCleaner $urlRewriteCleaner,
        ImportProductCollectionFactory $importProductCollectionFactory,
        AbstractDb $resource = null,
        MagentoAbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $readerUtils,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->productTypeFactory = $productTypeFactory;
        $this->filesystem = $filesystem;
        $this->commerceImportLogger = $commerceImportLogger;
        $this->productCollection = $this->getCollection();
        $this->rootDirectory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->categoryAssignationResource = $categoryAssignationResource;
        $this->urlRewriteCleaner = $urlRewriteCleaner;
        $this->importProductCollectionFactory = $importProductCollectionFactory;
    }

    public function setProductSkusFilter(array $productSkus)
    {
        $this->productSkus = $productSkus;
        $this->productCollection->addFieldToFilter('sku', ['in' => $productSkus]);
    }

    public function fetch()
    {
        $this->categoryAssignationResource->reset();
        $this->productCollection = $this->importProductCollectionFactory->create();

        /** @var $collection ProductCollection */
        $this->productCollection->setOrder('main_table.attribute_set_name')
            ->addFilter('market_id', $this->marketId)
            ->addFilter('lang_id', $this->langId)
            ->setOrder('main_table.id', 'asc');

        if (!empty($this->productSkus)) {
            $this->productCollection->addFieldToFilter('sku', ['in' => $this->productSkus]);
            $this->productSkus = [];
        }

        $this->productCollection->setRelationsConfiguration();
        $this->productCollection->setLinkedProducts();
        $this->prepareProducts();
        $this->handleChildrenRelations();

        sort($this->products);
        return $this->products;
    }

    /**
     * @param array $product
     */
    public function addProduct(array $product)
    {
        if (isset($product['ms3_id'])) {
            $this->products[$product['ms3_id']] = $product;
        }
    }

    protected function handleChildrenRelations()
    {
        foreach ($this->products as &$product) {
            if (isset($product['children']) && isset($product['product_type'])) {
                $productType = $this->productTypeFactory->create($product['product_type']);
                $productType->setProductChildrenConfiguration($product, $this->products);
                unset($product['children']);
            }
        }
    }

    private static function its() {
        return ['_s'=>microtime(true)];
    }
    private static function ts(&$s, $name) {
        $t = microtime(true);
        $s[$name] = $t-$s['_s'];
        $s['_s'] = $t;
    }

    protected function prepareProducts()
    {
        $this->products = [];
        $this->urlRewriteCleaner->cleanProductUrlRewrites();
        $this->getReaderUtils()->getConsoleOutput()->startProgress($this->productCollection->getSize());
        foreach ($this->productCollection as $item) {
            $ss = self::its();
            $this->getReaderUtils()->getConsoleOutput()->advanceProgress();
            $product = array_merge($this->defaultProductAttributes, $item->getData());

            if ($this->getReaderUtils()->getConfig()->isIgnoreInvalidImages()) {
                $this->removeInvalidImages($product);
            }

            self::ts($ss, 'img');

            $product = $this->getReaderUtils()
                ->getMapper()
                ->map($product, \Magento\Catalog\Model\Product::ENTITY, $this->getStore());
            $this->getReaderUtils()
                ->getEventManager()
                ->dispatch('reader_prepare_product_before', ['product' => $product]);
            self::ts($ss, 'map');
            $this->validateProduct($product);
            self::ts($ss, 'val');
            if (!$this->shouldAddProduct($product)) {
                $this->getReaderUtils()->getConsoleOutput()->comment(sprintf(
                    'Product not added [sku: %s]. Product not existing in Master Data',
                    $product['sku']
                ));
                continue;
            }
            $product['visibility'] = __('Catalog, Search');

            $this->handleSpecialChars($product, ['name']);
            self::ts($ss, 'handle chars');
            $this->setCustomAttributes($product);
            self::ts($ss, 'handle custom');
            $this->setProductCategories($product);
            self::ts($ss, 'handle cats');
            $this->setStoreAndWebsite($product);
            self::ts($ss, 'handle store');
            $this->setAdditionalImages($product);
            self::ts($ss, 'handle imgs');
            $this->setUniqueUrlKey($product);
            self::ts($ss, 'handle url');


            if ($product['product_type'] == 'virtual') {
                $product['visibility'] = __('Not Visible Individually');
            }

            if (false === $this->isPrimaryStructureMaster()) {
                $this->removeStructureMasterOnlyData($product);
            }
            $this->getReaderUtils()
                ->getEventManager()
                ->dispatch('reader_prepare_product_after', ['product' => $product]);

            $this->addProduct($product);
            self::ts($ss, 'end');
        }
        $this->getReaderUtils()->getConsoleOutput()->finishProgress();
    }

    public function cleanModelCache()
    {
        $this->getResource()->cleanAttributeValueCache();
        return parent::cleanModelCache();
    }

    protected function setCustomAttributes(array &$product)
    {
        $additional = [];
        if ($attributes = $this->getResource()->getProductAttributes($product['ms3_id'], $product['ms3_market_id'], $product['ms3_lang_id'])) {
            $this->handleSpecialChars($attributes, array_keys($attributes));
            foreach ($attributes as $code => $value) {
                $additional[] =
                    $code . MagentoProductImport::PAIR_NAME_VALUE_SEPARATOR . $value . '';
            }
            $product[MagentoProductExport::COL_ADDITIONAL_ATTRIBUTES] = implode(
                $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator(),
                $additional
            );
        }
    }

    protected function setProductCategories(array &$product)
    {
        $categoryAssignationMode = $this->getReaderUtils()->getConfig()->categoryAssignationMode();
        if ($categoryAssignationMode === CategoryAssignationMode::MODE_MS3_IDS) {
            $categories = $this->categoryAssignationResource->getCategoryIdsFromProductMsId($product['ms3_id'], $this->getStore());
        } else {
            $importCategory = $this->getReaderUtils()->getCategoryUtils()->getImportCategoryByStore($this->getStore());
            $rootCategory = $this->getReaderUtils()
                ->getCategoryUtils()
                ->getCategoryPathNamesByPathIds($importCategory->getPathIds());
            $categoryPaths = $this->getResource()->getProductCategoryPaths($product['ms3_id'], $rootCategory);
            $this->validateCategoryPaths($categoryPaths, $product);
            $categories = $categoryPaths;
        }

        if ($categories) {
            // prevent overwriting previously retrieved category assignations
            if (isset($this->categoriesBySku[$product['sku']])) {
                $categories = array_unique(array_merge($categories, $this->categoriesBySku[$product['sku']]));
            }
            $this->categoriesBySku[$product['sku']] = $categories;
            $product['categories'] = implode(
                $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator(),
                $categories
            );
        }
    }

    /**
     * @param array $categoryPaths
     * @param array $product
     * @throws \Exception
     */
    protected function validateCategoryPaths(array $categoryPaths, array $product)
    {
        foreach ($categoryPaths as $path) {
            if (!$this->getReaderUtils()->getCategoryUtils()->categoryPathExists($path)) {
                throw new \Exception(
                    sprintf('Product category not valid. Sku: %s | Category path: %s', $product['sku'], $path)
                );
            }
        }
    }

    protected function setStoreAndWebsite(array &$product)
    {
        if ($this->getStore()->getCode() == Store::ADMIN_CODE) {
            throw new \Exception('Admin store is not a valid store for product import');
        }
        if (!$this->isPrimaryStructureMaster()) {
            $product['store_view_code'] = $this->getStore()->getCode();
        }
        $product['product_websites'] = $this->getStore()->getWebsite()->getCode();
    }

    protected function setAdditionalImages(array &$product)
    {
        $images = (isset($product['additional_images']))
            ? explode(
                $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator(),
                $product['additional_images']
            )
            : [];
        $labels = (isset($product['additional_image_labels']))
            ? explode(
                $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator(),
                $product['additional_image_labels']
            )
            : [];
        for ($x = 2; $x <= parent::PRODUCT_MAX_IMAGES; ++$x) {
            if (isset($product['image_' . $x])) {
                $images[] = $product['image_' . $x];
                $labels[] = (isset($product['image_' . $x . '_label'])) ? $product['image_' . $x . '_label'] : '';
            }
            if (isset($product['ms_image_' . $x])) {
                $images[] = $product['ms_image_' . $x];
                $labels[] = (isset($product['ms_image_' . $x . '_label'])) ? $product['ms_image_' . $x . '_label'] : '';
            }
        }
        $product['additional_images'] = implode(
            $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator(),
            $images
        );
        $product['additional_image_labels'] = implode(
            $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator(),
            $labels
        );
    }

    protected function setUniqueUrlKey(array &$product)
    {
        if (!isset($product['url_key']) || null === $product['url_key']) {
            //@codingStandardsIgnoreLine
            $product['url_key'] = $product['name'];
        }
        $urlKey = $product['url_key'];
        if ($product['product_type'] != 'grouped') {
            if ($this->getReaderUtils()->getConfig()->canAddSkuToChildProductUrlKey()) {
                $urlKey .= '-' . $product['sku'];
            }
        } else {
            if ($this->getReaderUtils()->getConfig()->canAddSkuToParentProductUrlKey()) {
                $urlKey .= '-' . $product['sku'];
            }
        }
        $product['url_key'] = $this->getReaderUtils()->getProductUrlKey()
            ->getUniqueFormattedUrlKey($product['sku'], $urlKey);
    }

    protected function validateProduct(array $product)
    {
        if (!isset($product['sku']) || empty($product['sku'])) {
            throw new \Exception(sprintf('Product sku not defined [ms3_id: %s]', $product['ms3_id']));
        }

        if (!isset($product['name']) || empty($product['name'])) {
            throw new \Exception(sprintf('Product name not defined [sku: %s]', $product['sku']));
        }

        return true;
    }

    protected function shouldAddProduct(array $product)
    {
        if (!$this->isStructureMaster()
            && !$this->getReaderUtils()->getProductUtils()->isProductSkuImported($product['sku'])
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param array $product
     */
    protected function removeStructureMasterOnlyData(array &$product)
    {
        unset($product['attribute_set_code']);
        unset($product['product_type']);
        unset($product['qty']);
        unset($product['price']);
        unset($product['is_in_stock']);
        unset($product['children']);
    }

    protected function removeInvalidImages(array &$product)
    {
        for ($x=1; $x <= parent::PRODUCT_MAX_IMAGES; $x++) {
            if (isset($product['image_' . $x])) {
                $file = $this->getReaderUtils()->getConfig()->getImportFileDir() .
                    DIRECTORY_SEPARATOR .
                    $product['image_' . $x];

                if (!$this->rootDirectory->isExist($file)) {
                    $this->commerceImportLogger->warning(sprintf('[%s] File not found [%s]', $product['sku'], $file));
                    $product['image_' . $x] = null;
                    $product['image_' . $x . '_label'] = null;
                }

                if (!preg_match(Media::PATH_REGEXP, $file)) {
                    $this->commerceImportLogger->warn(sprintf('[%s] Invalid file name [%s]', $product['sku'], $file));
                    $product['image_' . $x] = null;
                    $product['image_' . $x . '_label'] = null;
                }
            }
        }
    }
}
