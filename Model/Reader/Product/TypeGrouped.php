<?php
/**
 * TypeGrouped
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Reader\Product;

use Magento\ImportExport\Model\Import;

class TypeGrouped implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function setProductChildrenConfiguration(array &$product, array $allProducts)
    {
        if (!isset($product['children']) || !is_array($product['children'])) {
            return;
        }

        $associatedProductsData = [];
        foreach ($product['children'] as $childrenId => $childrenData) {
            if (isset($allProducts[$childrenId]) && isset($allProducts[$childrenId]['sku'])) {
                $associatedProductsData[] = [
                    'sku' => $allProducts[$childrenId]['sku'],
                    'sort' => $childrenData['sort']
                ];
            }
        }

        $product['associated_skus'] = $this->getAssociatedSkusValue($associatedProductsData);
    }

    /**
     * Get final associated skus value for parent product
     *
     * IMPORTANT:
     * - At the moment that this was implemented the Magento\GroupedImportExport\Model\Import\Product\Type\Grouped
     * was wrongly using default separator instead the configured ones.
     * As all other Magento Import classes (Configurable, Bundle) properly use the configured separator,
     * Magento might also change that for Grouped class.
     *
     * See Magento\GroupedImportExport\Model\Import\Product\Type\Grouped:
     *  - $associatedSkusAndQtyPairs = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $associatedSkusQty);
     *
     * If Magento changes that. Please update the separator using the following method:
     * - Staempfli\CommerceImport\Model\Config::getMultipleValuesSeparator
     *
     * @param array $associatedProductsData
     * @return string
     */
    protected function getAssociatedSkusValue(array $associatedProductsData)
    {
        $associatedSkus = $this->getSortedAssociatedSkus($associatedProductsData);
        $associatedSkusValue = implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $associatedSkus);
        return $associatedSkusValue;
    }

    /**
     * Sort associated products by sort value and return only result sku list
     *
     * @param array $associatedProductsData
     * @return array
     */
    protected function getSortedAssociatedSkus(array $associatedProductsData)
    {
        // The following statements sorts array according to 'sort' key values
        usort($associatedProductsData, function ($a, $b) {
            return $a['sort'] <=> $b['sort'];
        });
        $associatedSkus = array_column($associatedProductsData, 'sku');
        return $associatedSkus;
    }
}
