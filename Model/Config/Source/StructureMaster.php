<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\StoreManagerInterface;
use Staempfli\CommerceImport\Model\Config;
use Magento\Store\Model\ScopeInterface;

/**
 * Class StructureMaster
 * @package Staempfli\CommerceImport\Model\Config\Source
 */
class StructureMaster implements ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfigInterface;
    /**
     * @var RequestInterface
     */
    protected $requestInterface;

    /**
     * StructureMaster constructor.
     * @param StoreManagerInterface $storeManagerInterface
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        ScopeConfigInterface $scopeConfigInterface,
        RequestInterface $requestInterface
    ) {
        $this->storeManagerInterface = $storeManagerInterface;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->requestInterface = $requestInterface;
    }

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $currentWebsiteId = ($this->requestInterface->getParam('website')) ?? $this->storeManagerInterface->getStore()->getWebsiteId();
            $stores = $this->storeManagerInterface->getStores(true);
            $this->_options[] = __('Choose Store View:');
            foreach ($stores as $store) {
                if ($currentWebsiteId === $store->getWebsiteId()) {
                    $market = ($this->getConfigValue(Config::XML_PATH_MAPPING_MARKET_ID, $store))
                        ? $this->getConfigValue(Config::XML_PATH_MAPPING_MARKET_ID, $store)
                        : __('undefined');
                    $lang = ($this->getConfigValue(Config::XML_PATH_MAPPING_LANG_ID, $store))
                        ? $this->getConfigValue(Config::XML_PATH_MAPPING_LANG_ID, $store)
                        : __('undefined');

                    $this->_options[$store->getId()] = sprintf(
                        '[Market: %s|Lang: %s] - %s',
                        $market,
                        $lang,
                        $store->getName()
                    );
                }
            }
        }
        return $this->_options;
    }

    /**
     * @param $path
     * @param $store
     * @return mixed
     */
    protected function getConfigValue($path, $store)
    {
        return $this->scopeConfigInterface->getValue($path, ScopeInterface::SCOPE_STORE, $store->getCode());
    }
}
