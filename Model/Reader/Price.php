<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Reader;

use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing;
use Magento\Framework\Data\Collection\AbstractDb as MagentoAbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Staempfli\CommerceImport\Api\Data\PriceReaderInterface;
use Staempfli\CommerceImport\Model\AbstractReader;
use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;
use Staempfli\CommerceImport\Model\Utils\Reader as ReaderUtils;

/**
 * Class Product
 * @package Staempfli\CommerceImport\Model\Reader
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class Price extends AbstractReader implements PriceReaderInterface
{
    /**
     * @var array
     */
    protected $productIdSkuPairs = [];
    /**
     * @var array
     */
    protected $productPricesToImport = [];
    /**
     * @var array
     */
    protected $pricesData = [];
    /**
     * @var  \Staempfli\CommerceImport\Model\ResourceModel\Price\Collection
     */
    protected $priceCollection;
    /**
     * @var \Staempfli\CommerceImport\Model\ResourceModel\Product\Collection
     */
    protected $productCollection;
    /**
     * @var string
     */
    protected $websiteCode;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('Staempfli\CommerceImport\Model\ResourceModel\Price');
    }

    /**
     * Price constructor.
     * @param ProductFactory $productFactory
     * @param ReaderUtils $readerUtils
     * @param Context $context
     * @param Registry $registry
     * @param AbstractDb|null $resource
     * @param MagentoAbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ProductFactory $productFactory,
        ReaderUtils $readerUtils,
        Context $context,
        Registry $registry,
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
        $this->productCollection = $productFactory->create()->getCollection();
    }

    public function clearInstance()
    {
        $this->productIdSkuPairs = [];
        $this->productPricesToImport = [];
        $this->pricesData = [];
        if ($this->priceCollection) $this->priceCollection->resetData();
        if ($this->productCollection) $this->productCollection->resetData();
        $this->websiteCode = null;
        return parent::clearInstance();
    }


    /**
     * @param array $productSkus
     */
    public function setProductSkusFilter(array $productSkus)
    {
        $this->productCollection->addFieldToFilter('sku', ['in' => $productSkus]);
    }

    /**
     * @return array
     */
    public function fetch()
    {
        $this->getReaderUtils()->getConsoleOutput()->plain('Preparing Prices to import');
        $this->productIdSkuPairs = $this->productCollection->getIdSkuPairs();
        $productPrices = $this->getProductPricesToImport();

        $this->getReaderUtils()->getConsoleOutput()->startProgress(count($productPrices));
        foreach ($productPrices as $productId => $prices) {
            $this->getReaderUtils()->getConsoleOutput()->advanceProgress();
            $sku = $this->productIdSkuPairs[$productId]??null;
            if ($sku) {
                $this->setPriceDataForSku($sku, $prices);
            }
        }
        $this->getReaderUtils()->getConsoleOutput()->finishProgress();
        return $this->pricesData;
    }

    /**
     * @return mixed
     */
    protected function getProductPricesToImport()
    {
        if (!$this->productPricesToImport) {
            $tierPricesAttributes = $this->getCollection()
                ->addFilter('market_id', $this->marketId)
                ->addFilter('lang_id', $this->langId)
                ->addAttributeValues();
            foreach ($tierPricesAttributes as $tierAttribute) {
                $suffix = $this->getReaderUtils()->getTierPrice()->getAttributeSuffix($tierAttribute->getCode());
                foreach ($tierAttribute->getAttributeValues() as $productId => $value) {
                    $this->productPricesToImport[$productId][$suffix][$this->getReaderUtils()->getTierPrice()->getValueType($tierAttribute->getCode())] = $value;//@codingStandardsIgnoreLine
                }
            }
        }
        return $this->productPricesToImport;
    }

    /**
     * @param $sku
     * @param array $prices
     */
    protected function setPriceDataForSku($sku, array $prices)
    {
        foreach ($prices as $tierPriceData) {
            if ($this->isValidTierPrice($tierPriceData)) {
                $this->pricesData[] = [
                    AdvancedPricing::COL_SKU => $sku,
                    AdvancedPricing::COL_TIER_PRICE_WEBSITE => $this->getWebsiteCode(),
                    AdvancedPricing::COL_TIER_PRICE_CUSTOMER_GROUP => AdvancedPricing::VALUE_ALL_GROUPS,
                    AdvancedPricing::COL_TIER_PRICE_QTY => $tierPriceData['qty'],
                    AdvancedPricing::COL_TIER_PRICE => $tierPriceData['price']
                ];
            }
        }
    }

    /**
     * @return mixed
     */
    protected function getWebsiteCode()
    {
        if (!$this->websiteCode) {
            $this->websiteCode = $this->getStore()->getWebsite()->getCode();
        }
        return $this->websiteCode;
    }

    /**
     * @param array $tierPriceData
     * @return bool
     */
    protected function isValidTierPrice(array $tierPriceData)
    {
        if ($tierPriceData['qty'] > 0 || $tierPriceData['price'] > 0) {
            return true;
        }
        return false;
    }
}
