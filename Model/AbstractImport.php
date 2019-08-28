<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model;

use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Event\Manager as EventManager;
use Staempfli\CommerceImport\Model\Utils\Import as ImportUtils;

abstract class AbstractImport
{
    /**
     * @var string
     */
    protected $area = \Magento\Framework\App\Area::AREA_ADMINHTML;
    /**
     * @var ImportUtils
     */
    private $importUtils;

    public function __construct(
        ImportUtils $importUtils
    ) {
        $this->importUtils = $importUtils;
    }

    /**
     * @param string $area
     */
    public function setArea($area = \Magento\Framework\App\Area::AREA_ADMINHTML)
    {
        $this->area = $area;
    }

    /**
     * @return ImportUtils
     */
    public function getImportUtils()
    {
        return $this->importUtils;
    }
}
