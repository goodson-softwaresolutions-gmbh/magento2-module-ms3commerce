<?php
/**
 * DeleteOldProductCategoryAssignationPlugin
 *
 * @copyright Copyright © 2017 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Plugin;

use Magento\CatalogImportExport\Model\Import\Product;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\ResourceModel\Category\CategoryAssignationResource;

class DeleteOldProductCategoryAssignationPlugin
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CategoryAssignationResource
     */
    private $categoryAssignationResource;

    public function __construct(Config $config, CategoryAssignationResource $categoryAssignationResource)
    {
        $this->config = $config;
        $this->categoryAssignationResource = $categoryAssignationResource;
    }

    /**
     * @param Product $subject
     * @param array $entityRowsIn
     * @param array $entityRowsUp
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveProductEntity(Product $subject, array $entityRowsIn, array $entityRowsUp)
    {
        if ($this->config->deleteOldCategoryAssignations()) {
            $productIds = array_map(function ($product) {
                return $product['entity_id'];
            }, $entityRowsUp);
            $this->categoryAssignationResource->deleteProductAssignations($productIds);
        }

        return [$entityRowsIn, $entityRowsUp];
    }
}
