<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils;

use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeSetFactory;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeGroupFactory;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeFactory;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Event\Manager as EventManager;
use Staempfli\CommerceImport\Model\Utils\Entity\CategoryFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\ProductFactory;
use Staempfli\CommerceImport\Model\Price\TierPrice;

/**
 * Class AbstractUtils
 * @package Staempfli\CommerceImport\Model\Utils
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractUtils
{
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Store
     */
    private $store;
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Attribute\AttributeSet
     */
    private $attributeSetUtils;
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Attribute\Attribute
     */
    private $attributeUtils;
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Entity\Category
     */
    private $categoryUtils;
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Entity\Product
     */
    private $productUtils;
    /**
     * @var EventManager
     */
    private $eventManager;
    /**
     * @var ConsoleOutput
     */
    private $consoleOutput;
    /**
     * @var StoreFactory
     */
    private $storeFactory;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TierPrice
     */
    private $tierPrice;
    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;
    /**
     * @var AttributeFactory
     */
    private $attributeFactory;
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * AbstractUtils constructor.
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
        $this->eventManager = $eventManager;
        $this->consoleOutput = $consoleOutput;
        $this->storeFactory = $storeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeFactory = $attributeFactory;
        $this->config = $config;
        $this->categoryFactory = $categoryFactory;
        $this->productFactory = $productFactory;
        $this->tierPrice = $tierPrice;
    }

    /**
     * @return Store
     */
    public function getStore()
    {
        if (!$this->store) {
            return $this->storeFactory->create();
        }
        return $this->store;
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * @return ConsoleOutput
     */
    public function getConsoleOutput()
    {
        return $this->consoleOutput;
    }

    /**
     * @return Attribute\AttributeSet
     */
    public function getAttributeSetUtils()
    {
        if (!$this->attributeSetUtils) {
            $this->attributeSetUtils = $this->attributeSetFactory->create();
        }
        return $this->attributeSetUtils;
    }

    /**
     * @return Attribute\Attribute
     */
    public function getAttributeUtils()
    {
        if (!$this->attributeUtils) {
            $this->attributeUtils = $this->attributeFactory->create();
        }
        return $this->attributeUtils;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Entity\Category
     */
    public function getCategoryUtils()
    {
        if (!$this->categoryUtils) {
            $this->categoryUtils = $this->categoryFactory->create();
        }
        return $this->categoryUtils;
    }

    /**
     * @return Entity\Product
     */
    public function getProductUtils()
    {
        if (!$this->productUtils) {
            $this->productUtils = $this->productFactory->create();
        }
        return $this->productUtils;
    }

    /**
     * @return TierPrice
     */
    public function getTierPrice()
    {
        return $this->tierPrice;
    }

    public function reset() {
        $this->productUtils = null;
        $this->categoryUtils = null;
        $this->attributeUtils = null;
        $this->attributeSetUtils = null;
        $this->store = null;
    }
}
