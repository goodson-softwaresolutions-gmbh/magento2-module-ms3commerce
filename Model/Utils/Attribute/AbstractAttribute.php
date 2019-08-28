<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils\Attribute;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;

abstract class AbstractAttribute
{
    /**
     * @var EavSetup
     */
    private $eavSetup;
    /**
     * @var EavConfig
     */
    private $eavConfig;

    public function __construct(
        EavSetup $eavSetup,
        EavConfig $eavConfig
    ) {
        $this->eavSetup = $eavSetup;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @return EavSetup
     */
    protected function getEavSetup()
    {
        return $this->eavSetup;
    }

    /**
     * @return EavConfig
     */
    protected function getEavConfig()
    {
        return $this->eavConfig;
    }
}
