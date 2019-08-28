<?php
/**
 * AllProductTypes
 *
 * @copyright Copyright (c) 2017 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Plugin;

use Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType as AbstractProductType;

class ProductEmptyDataPlugin
{
    /**
     * Do not clear empty data for products import. Otherwise we cannot unset values that already have a value
     *
     * @param AbstractProductType $subject
     * @param callable $proceed
     * @param array $rowData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundClearEmptyData(AbstractProductType $subject, callable $proceed, array $rowData) //@codingStandardsIgnoreLine
    {
        return $this->doNotClearEmptyData($rowData);
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function doNotClearEmptyData(array $rowData)
    {
        return $rowData;
    }
}
