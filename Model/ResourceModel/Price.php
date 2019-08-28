<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel;

use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Price
 * @package Staempfli\CommerceImport\Model\ResourceModel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Price extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('m2m_attribute', 'id');
    }
}
