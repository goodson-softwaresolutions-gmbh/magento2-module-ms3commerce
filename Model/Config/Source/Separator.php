<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\ImportExport\Model\Import;

/**
 * Class Separator
 * @package Staempfli\CommerceImport\Model\Config\Source
 */
class Separator implements ArrayInterface
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
                ['value' => ',', 'label' => ','],
                ['value' => ';', 'label' => ';'],
                ['value' => '§', 'label' => '§'],
                ['value' => '#', 'label' => '#'],
            ];
        }
        return $this->_options;
    }
}
