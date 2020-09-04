<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Import\Processor;

use Magento\Catalog\Model\Product;
use Magento\ImportExport\Model\Import as MagentoImport;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;

class ProductImportProcessor extends AbstractImportProcessor
{
    public function updateProductCategoryAssignation() : bool
    {
        try {
            $this->categoryAssignation->updateAssignationPosition();
        } catch (\Exception $e) {
            $this->getImportModel()->getErrorAggregator()->addError(
                'ProductCategorySortingError',
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
            return false;
        }
        return true;
    }

    protected function getSettings() : array
    {
        return [
            'entity' => Product::ENTITY,
            'behavior' => $this->config->getBehavior(),
            MagentoImport::FIELD_NAME_VALIDATION_STRATEGY => $this->config->getValidationStrategy(),
            MagentoImport::FIELD_NAME_ALLOWED_ERROR_COUNT => $this->config->getAllowedErrorCount(),
            MagentoImport::FIELD_NAME_IMG_FILE_DIR => $this->config->getImportFileDir(),
            MagentoImport::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => $this->config->getMultipleValuesSeparator()
        ];
    }
}
