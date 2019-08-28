<?php
/**
 * ProductCategoryAssignationPlugin
 *
 * @copyright Copyright © 2017 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Plugin;

use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Config\Source\CategoryAssignationMode;

class ProductCategoryAssignationPlugin
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param CategoryProcessor $subject
     * @param callable $proceed
     * @param $categoriesString
     * @param $categoriesSeparator
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundUpsertCategories(
        CategoryProcessor $subject,
        callable $proceed,
        $categoriesString,
        $categoriesSeparator
    ) {
        if ($this->config->categoryAssignationMode() === CategoryAssignationMode::MODE_MS3_IDS) {
            return explode($categoriesSeparator, $categoriesString);
        }

        return $proceed($categoriesString, $categoriesSeparator);
    }
}
