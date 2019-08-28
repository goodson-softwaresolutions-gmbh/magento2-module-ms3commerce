<?php
/**
 * TierPrice
 *
 * @copyright Copyright (c) 2017 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Price;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Staempfli\CommerceImport\Model\Config;

class TierPrice
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param WebsiteInterface $website
     * @return int
     */
    public function getWebsiteIdForTierPrice(WebsiteInterface $website)
    {
        return $this->isPriceGlobal() ? 0 : $website->getId();
    }

    /**
     * Is Global Price
     *
     * @return bool
     */
    protected function isPriceGlobal()
    {
        $priceScope = $this->scopeConfig->getValue(CatalogHelper::XML_PATH_PRICE_SCOPE);
        return $priceScope == CatalogHelper::PRICE_SCOPE_GLOBAL;
    }

    /**
     * @return mixed
     */
    public function getPricePrefix()
    {
        return $this->scopeConfig->getValue(Config::XML_PATH_PRICE_TIER_PRICE_PREFIX);
    }

    /**
     * @return mixed
     */
    public function getQtyPrefix()
    {
        return $this->scopeConfig->getValue(Config::XML_PATH_PRICE_TIER_QTY_PREFIX);
    }

    /**
     * @param string $attributeCode
     * @return string
     * @throws \Exception
     */
    public function getAttributeSuffix(string $attributeCode)
    {
        if ($this->isQtyAttribute($attributeCode)) {
            return substr($attributeCode, strlen($this->getQtyPrefix()));
        }
        if ($this->isPriceAttribute($attributeCode)) {
            return substr($attributeCode, strlen($this->getPricePrefix()));
        }
        throw new \Exception(sprintf('Attribute Code "%s" doesn\'t have a valid suffix', $attributeCode));
    }

    /**
     * @param string $attributeCode
     * @return string
     * @throws \Exception
     */
    public function getValueType(string $attributeCode)
    {
        if ($this->isQtyAttribute($attributeCode)) {
            return 'qty';
        }
        if ($this->isPriceAttribute($attributeCode)) {
            return 'price';
        }
        throw new \Exception(sprintf('Attribute Code "%s" doesn\'t have a valid value type', $attributeCode));
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    protected function isQtyAttribute(string $attributeCode)
    {
        if (strpos($attributeCode, $this->getQtyPrefix()) === 0) {
            return true;
        }
        return false;
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    protected function isPriceAttribute(string $attributeCode)
    {
        if (strpos($attributeCode, $this->getPricePrefix()) === 0) {
            return true;
        }
        return false;
    }
}
