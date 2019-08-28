<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel\Db;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

/**
 * Class AbstractDb
 * @package Staempfli\CommerceImport\Model\ResourceModel\Db
 */
abstract class AbstractDb extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * {@inheritdoc}
     * Set default database to connect to CommerceImportSetupConfig::DB_CONNECTION_SETUP
     */
    public function __construct(Context $context, $connectionName = CommerceImportSetupConfig::DB_CONNECTION_SETUP) //@codingStandardsIgnoreLine
    {
        parent::__construct($context, $connectionName);
    }
}
