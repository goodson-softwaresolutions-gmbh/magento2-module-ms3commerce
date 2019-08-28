<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Class ValidationStrategy
 * @package Staempfli\CommerceImport\Model\Config\Source
 */
class ValidationStrategy implements ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [
                [
                    'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR,
                    'label' => __('Stop on Error')
                ],
                [
                    'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS,
                    'label' => __('Skip error entries')
                ],
            ];
        }

        return $this->_options;
    }
}
