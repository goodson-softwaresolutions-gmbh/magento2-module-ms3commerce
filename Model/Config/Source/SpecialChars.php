<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Class SpecialChars
 * @package Staempfli\CommerceImport\Model\Config\Source
 */
class SpecialChars implements ArrayInterface
{
    const SPECIAL_CHARS_REMOVE = 'remove';
    const SPECIAL_CHARS_REPLACE = 'replace';

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
                    'value' => self::SPECIAL_CHARS_REMOVE,
                    'label' => __('Remove')
                ],
                [
                    'value' => self::SPECIAL_CHARS_REPLACE,
                    'label' => __('Replace')
                ],
            ];
        }

        return $this->_options;
    }
}
