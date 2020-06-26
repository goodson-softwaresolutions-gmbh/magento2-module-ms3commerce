<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model;

use Magento\Framework\Data\Collection\AbstractDb as MagentoAbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Staempfli\CommerceImport\Helper\Data as CommerceHelper;
use Staempfli\CommerceImport\Model\Reader\AttributeFactory;
use Staempfli\CommerceImport\Model\Utils\Reader as ReaderUtils;
use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;
use Staempfli\CommerceImport\Model\Reader\PriceFactory;
use Staempfli\CommerceImport\Setup\ImportDatabaseSetup;

/**
 * Class Reader
 * @package Staempfli\CommerceImport\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class Reader extends AbstractReader
{
    const IMPORT_DATABASE_TABLES = [
        ImportDatabaseSetup::ATTRIBUTE_TABLE,
        ImportDatabaseSetup::ATTRIBUTE_OPTION_TABLE,
        ImportDatabaseSetup::ATTRIBUTE_SET_TABLE,
        ImportDatabaseSetup::ATTRIBUTE_VALUE_TABLE,
        ImportDatabaseSetup::ATTRIBUTE_VARIANT_TABLE,
        ImportDatabaseSetup::PRODUCT_TABLE,
        ImportDatabaseSetup::PRODUCT_CATEGORY_TABLE,
        ImportDatabaseSetup::PRODUCT_LINK_TABLE,
        ImportDatabaseSetup::PRODUCT_RELATION_TABLE,
        ImportDatabaseSetup::CATEGORY_TABLE,
    ];

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;
    /**
     * @var PriceFactory
     */
    protected $priceFactory;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('Staempfli\CommerceImport\Model\ResourceModel\Product');
    }

    /**
     * @param AttributeFactory $attributeFactory
     * @param PriceFactory $priceFactory
     * @param ReaderUtils $readerUtils
     * @param Context $context
     * @param Registry $registry
     * @param AbstractDb $resource
     * @param MagentoAbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        PriceFactory $priceFactory,
        ReaderUtils $readerUtils,
        Context $context,
        Registry $registry,
        AbstractDb $resource = null,
        MagentoAbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $readerUtils,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->attributeFactory = $attributeFactory;
        $this->priceFactory = $priceFactory;
    }

    /**
     * @throws \Exception
     */
    public function validateDatabase()
    {
        $tables = $this->showTables();
        if (!$tables) {
            throw new \Exception('Source database seems to be empty, please import data first by execute: "bin/magento ms3:databse:import --file="');//@codingStandardsIgnoreLine
        }
        $diff = array_diff(self::IMPORT_DATABASE_TABLES, array_keys($tables));
        if ($diff) {
            throw new \Exception('Missing Tables found! [' . implode(',', $diff) . ']');
        }
    }

    /**
     * @throws \Exception
     */
    public function validateConfig()
    {
        $store = $this->getStore();
        if (null === $store->getCode() || $store->getCode() === 'admin') {
            //@codingStandardsIgnoreLine
            throw new \Exception(sprintf('No mapping configured, please add a mapping in Settings > Configuration > Services > mS3 Commerce Import'));//@codingStandardsIgnoreLine
        }
        return true;
    }

    public function prepareDatabase()
    {
        $this->getConnection()->query('SET SESSION group_concat_max_len = 65536');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getImportInformation()
    {
        $data = [];
        foreach ($this->getMarketAndLangIds() as $marketAndLangId) {
            $row = $marketAndLangId;
            $row['total_products'] = $this->getConnection()->fetchOne(sprintf(
                "SELECT COUNT(*) FROM %s WHERE market_id = %s AND lang_id = %s",
                'm2m_product',
                $marketAndLangId['market_id'],
                $marketAndLangId['lang_id']
            ));
            $row['total_categories'] = $this->getConnection()->fetchOne(sprintf(
                "SELECT COUNT(*) FROM %s WHERE `level` > 0 AND market_id = %s AND lang_id = %s",
                'm2m_category',
                $marketAndLangId['market_id'],
                $marketAndLangId['lang_id']
            ));
            $row['total_attributes'] = $this->attributeFactory->create()->getCollection()->getSize();
            $row['total_prices'] = $this->priceFactory->create()->getCollection()
                ->addFilter('market_id', $marketAndLangId['market_id'])
                ->addFilter('lang_id', $marketAndLangId['lang_id'])
                ->getSize();
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function showTables()
    {
        return $this->getConnection()->fetchAssoc("SHOW TABLES");
    }
}
