<?php

namespace Staempfli\CommerceImport\Model;

use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewrite as UrlRewriteResource;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class UrlRewriteCleaner
{
    /**
     * @var UrlRewriteCollectionFactory
     */
    private $urlRewriteCollectionFactory;
    /**
     * @var ProductResource
     */
    private $productResource;
    /**
     * @var UrlRewriteResource
     */
    private $urlRewriteResource;

    /**
     * UrlRewriteCleaner constructor.
     * @param UrlRewriteCollectionFactory $urlRewriteCollectionFactory
     * @param UrlRewriteResource $urlRewriteResource
     * @param ProductResource $productResource
     */
    public function __construct(
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        UrlRewriteResource $urlRewriteResource,
        ProductResource\Proxy $productResource
    ) {
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->productResource = $productResource;
        $this->urlRewriteResource = $urlRewriteResource;
    }

    public function cleanProductUrlRewrites()
    {
        /** @var UrlRewriteCollection $urlRewriteCollection */
        $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
        $urlRewriteCollection->addFieldToFilter('entity_type', 'product');

        foreach ($urlRewriteCollection as $urlRewrite) {
            /** @var UrlRewrite $urlRewrite*/
            $storeId = $urlRewrite->getStoreId();
            $productId = $urlRewrite->getEntityId();

            $urlPath = $this->productResource->getAttributeRawValue($productId, 'url_key', $storeId);
            if (is_array($urlPath)) {
                $urlPath = current($urlPath);
            }

            if ($urlPath . '.html' != $urlRewrite->getData('request_path')) {
                $this->urlRewriteResource->delete($urlRewrite);
            }
        }
    }
}
