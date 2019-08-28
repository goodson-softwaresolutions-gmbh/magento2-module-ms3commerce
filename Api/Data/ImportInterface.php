<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Api\Data;

/**
 * @api
 */
interface ImportInterface
{
    /**
     * @return array
     */
    public function prepare();

    /**
     * @throws \Exception
     */
    public function validate();

    /**
     * Import Attribute
     *
     * @return void
     */
    public function import();

    /**
     * Clear Memory attributes after import
     *
     * @return void
     */
    public function clearMemory();

    /**
     * @param string $area
     */
    public function setArea($area = \Magento\Framework\App\Area::AREA_ADMINHTML);
}
