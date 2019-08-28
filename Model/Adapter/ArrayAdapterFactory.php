<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Adapter;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class ArrayAdapterFactory
 * @package Staempfli\CommerceImport\Model\Adapter
 */
class ArrayAdapterFactory
{
    protected $_objectManager = null;

    protected $_instanceName = null;

    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = 'Staempfli\CommerceImport\Model\Adapter\ArrayAdapter'
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * @param array $data
     * @return \Staempfli\CommerceImport\Model\Adapter\ArrayAdapter
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
