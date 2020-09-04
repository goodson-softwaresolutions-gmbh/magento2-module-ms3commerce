<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils;

use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Event\Manager as EventManager;
use Staempfli\CommerceImport\Model\Price\TierPrice;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeFactory;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeGroupFactory;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeSetFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\CategoryFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\Product\MediaFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\ProductFactory;

/**
 * Class import
 * @package Staempfli\CommerceImport\Model\Utils
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Import extends AbstractUtils
{
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Attribute\AttributeGroup
     */
    private $attributeGroupUtils;
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Entity\Product\Media
     */
    private $productMediaUtils;
    /**
     * @var AttributeGroupFactory
     */
    private $attributeGroupFactory;
    /**
     * @var MediaFactory
     */
    private $mediaFactory;

    /**
     * Import constructor.
     * @param AttributeGroupFactory $attributeGroupFactory
     * @param MediaFactory $mediaFactory
     * @param EventManager $eventManager
     * @param ConsoleOutput $consoleOutput
     * @param StoreFactory $storeFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param AttributeFactory $attributeFactory
     * @param Config $config
     * @param CategoryFactory $categoryFactory
     * @param ProductFactory $productFactory
     * @param TierPrice $tierPrice
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AttributeGroupFactory $attributeGroupFactory,
        MediaFactory $mediaFactory,
        EventManager $eventManager,
        ConsoleOutput $consoleOutput,
        StoreFactory $storeFactory,
        AttributeSetFactory $attributeSetFactory,
        AttributeFactory $attributeFactory,
        Config $config,
        CategoryFactory $categoryFactory,
        ProductFactory $productFactory,
        TierPrice $tierPrice
    ) {
        parent::__construct(
            $eventManager,
            $consoleOutput,
            $storeFactory,
            $attributeSetFactory,
            $attributeFactory,
            $config,
            $categoryFactory,
            $productFactory,
            $tierPrice
        );
        $this->attributeGroupFactory = $attributeGroupFactory;
        $this->mediaFactory = $mediaFactory;
    }

    /**
     * @return Attribute\AttributeGroup
     */
    public function getAttributeGroupUtils()
    {
        if (!$this->attributeGroupUtils) {
            $this->attributeGroupUtils = $this->attributeGroupFactory->create();
        }
        return $this->attributeGroupUtils;
    }

    /**
     * @return Entity\Product\Media
     */
    public function getProductMediaUtils()
    {
        if (!$this->productMediaUtils) {
            $this->productMediaUtils = $this->mediaFactory->create();
        }
        return $this->productMediaUtils;
    }

    public function reset()
    {
        parent::reset();
        $this->productMediaUtils = null;
        $this->attributeGroupUtils = null;
    }
}
