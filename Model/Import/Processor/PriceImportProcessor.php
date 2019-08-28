<?php
/**
 * PriceImportProcessor
 *
 * @copyright Copyright (c) 2017 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Import\Processor;

use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing;
use Magento\ImportExport\Model\Import as MagentoImport;

class PriceImportProcessor extends AbstractImportProcessor
{
    /**
     * @return array
     */
    protected function getSettings()
    {
        return [
            'entity' => AdvancedPricing::ENTITY_TYPE_CODE,
            'behavior' => MagentoImport::BEHAVIOR_APPEND,
            MagentoImport::FIELD_NAME_VALIDATION_STRATEGY => $this->config->getValidationStrategy(),
            MagentoImport::FIELD_NAME_ALLOWED_ERROR_COUNT => $this->config->getAllowedErrorCount(),
            MagentoImport::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => $this->config->getMultipleValuesSeparator()
        ];
    }
}
