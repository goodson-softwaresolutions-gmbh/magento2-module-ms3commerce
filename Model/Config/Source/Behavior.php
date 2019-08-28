<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\ImportExport\Model\Import;

/**
 * Class Behavior
 * @package Staempfli\CommerceImport\Model\Config\Source
 */
class Behavior implements ArrayInterface
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
                ['value' => Import::BEHAVIOR_APPEND, 'label' => __('Add/Update')],
                ['value' => Import::BEHAVIOR_REPLACE, 'label' => __('Replace')],
                ['value' => Import::BEHAVIOR_DELETE, 'label' => __('Delete')],
            ];
        }
        return $this->_options;
    }
}
