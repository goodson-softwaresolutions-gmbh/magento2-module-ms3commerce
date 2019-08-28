<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils;

use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Event\Manager as EventManager;
use Staempfli\CommerceImport\Model\Price\TierPrice;
use Staempfli\CommerceImport\Model\Reader\Entity\Mapper;
use Magento\Framework\Escaper;
use Staempfli\CommerceImport\Model\UrlKey\CategoryUrlKey;
use Staempfli\CommerceImport\Model\UrlKey\ProductUrlKey;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeFactory;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeSetFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\CategoryFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\ProductFactory;

/**
 * Class Reader
 * @package Staempfli\CommerceImport\Model\Utils
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Reader extends AbstractUtils
{
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var Mapper
     */
    private $mapper;
    /**
     * @var SpecialChars
     */
    private $specialChars;
    /**
     * @var ProductUrlKey
     */
    private $productUrlKey;
    /**
     * @var CategoryUrlKey
     */
    private $categoryUrlKey;

    /**
     * Reader constructor.
     * @param Escaper $escaper
     * @param Mapper $mapper
     * @param SpecialChars $specialChars
     * @param ProductUrlKey $productUrlKey
     * @param CategoryUrlKey $categoryUrlKey
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
        Escaper $escaper,
        Mapper $mapper,
        SpecialChars $specialChars,
        ProductUrlKey $productUrlKey,
        CategoryUrlKey $categoryUrlKey,
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
        $this->escaper = $escaper;
        $this->mapper = $mapper;
        $this->specialChars = $specialChars;
        $this->productUrlKey = $productUrlKey;
        $this->categoryUrlKey = $categoryUrlKey;
    }

    /**
     * @return Escaper
     */
    public function getEscaper()
    {
        return $this->escaper;
    }

    /**
     * @return Mapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @return SpecialChars
     */
    public function getSpecialChars()
    {
        return $this->specialChars;
    }

    /**
     * @return ProductUrlKey
     */
    public function getProductUrlKey()
    {
        return $this->productUrlKey;
    }

    /**
     * @return CategoryUrlKey
     */
    public function getCategoryUrlKey()
    {
        return $this->categoryUrlKey;
    }
}
