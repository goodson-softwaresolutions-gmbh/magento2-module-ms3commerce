<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model;

use Magento\Framework\Data\Collection\AbstractDb as MagentoAbstractDb;
use Staempfli\CommerceImport\Model\Config\Source\SpecialChars;
use Staempfli\CommerceImport\Model\Utils\Reader as ReaderUtils;
use Staempfli\CommerceImport\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Staempfli\CommerceImport\Model\Utils\Store;

/**
 * Class AbstractReader
 * @package Staempfli\CommerceImport\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
abstract class AbstractReader extends \Magento\Framework\Model\AbstractModel
{
    const ATTRIBUTE_ENTITY_CATEGORY = 1;
    const ATTRIBUTE_ENTITY_PRODUCT = 2;

    const ATTRIBUTE_SCOPE_GLOBAL = 0;
    const ATTRIBUTE_SCOPE_WEBSITE = 1;
    const ATTRIBUTE_SCOPE_STORE = 2;

    const ATTRIBUTE_TYPE_TEXT = 0;
    const ATTRIBUTE_TYPE_TEXTAREA = 1;
    const ATTRIBUTE_TYPE_DATE = 2;
    const ATTRIBUTE_TYPE_BOOLEAN = 3;
    const ATTRIBUTE_TYPE_MULITSELECT = 4;
    const ATTRIBUTE_TYPE_SELECT = 5;

    const PRODUCT_MAX_IMAGES = 15;

    /**
     * @var int
     */
    protected $marketId = 0;
    /**
     * @var int
     */
    protected $langId = 0;
    /**
     * @var Store
     */
    protected $store;
    /**
     * @var Store[]
     */
    protected $stores;
    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $connection;
    /**
     * @var ReaderUtils
     */
    private $readerUtils;

    /**
     * AbstractReader constructor.
     * @param ReaderUtils $readerUtils
     * @param Context $context
     * @param Registry $registry
     * @param AbstractDb|null $resource
     * @param MagentoAbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ReaderUtils $readerUtils,
        Context $context,
        Registry $registry,
        AbstractDb $resource = null,
        MagentoAbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->readerUtils = $readerUtils;
    }

    /**
     * @param $text
     * @return array|string
     */
    public function escapeHtml($text)
    {
        return $this->getReaderUtils()->getEscaper()->escapeHtml($text);
    }

    /**
     * @return array
     */
    public function getMarketAndLangId()
    {
        if (!$this->marketId || !$this->langId) {
            $data = $this->getConnection()
                ->fetchRow(sprintf("SELECT market_id, lang_id FROM %s LIMIT 1", 'm2m_product'));
            list($this->marketId, $this->langId) = array_values($data);
        }
        return [
            'market_id' => $this->marketId,
            'lang_id' => $this->langId
        ];
    }

    /**
     * @return bool|int|\Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
            $marketAndLang = $this->getMarketAndLangId();
            return $this->getReaderUtils()
                ->getStore()
                ->getStoreByMarketAndLang($marketAndLang['market_id'], $marketAndLang['lang_id']);
    }

    public function setMarketAndLangId($marketId, $langId)
    {
        $this->marketId = $marketId;
        $this->langId = $langId;
    }

    /**
     * @return array
     */
    public function getMarketAndLangIds()
    {
        return $this->getConnection()
            ->fetchAll(sprintf(
                "SELECT DISTINCT market_id, lang_id FROM %s ORDER BY market_id ASC, lang_id ASC",
                'm2m_product'
            ));
    }

    /**
     * @return Store[]
     */
    public function getStores()
    {
        if (!$this->stores) {
            $this->stores = [];
            foreach ($this->getMarketAndLangIds() as $marketAndLangId) {
                $this->stores[] = $this->getReaderUtils()->getStore()->getStoreByMarketAndLang(
                    $marketAndLangId['market_id'],
                    $marketAndLangId['lang_id']
                );
            }
        }
        return $this->stores;
    }

    /**
     * @return bool
     */
    public function isStructureMaster()
    {
        return $this->getReaderUtils()->getConfig()->isStructureMaster($this->getStore());
    }

    /**
     * @return bool
     */
    public function isPrimaryStructureMaster()
    {
        return $this->getReaderUtils()->getConfig()->isPrimaryStructureMaster($this->getStore());
    }

    /**
     * @return false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->getResource()->getConnection();
        }
        return $this->connection;
    }

    /**
     * @return ReaderUtils
     */
    public function getReaderUtils()
    {
        return $this->readerUtils;
    }

    public function clearInstance()
    {
        $this->marketId = 0;
        $this->langId = 0;
        $this->stores = null;
        $this->store = null;
        $this->readerUtils->reset();
        return parent::clearInstance();
    }

    /**
     * @param array $entityData
     * @param array $fields
     */
    protected function handleSpecialChars(array &$entityData, array $fields)
    {
        $specialChars = $this->getReaderUtils()->getSpecialChars();
        if ($this->getReaderUtils()->getConfig()->getSpecialCharsHandling() === SpecialChars::SPECIAL_CHARS_REMOVE) {
            $specialChars->removeSpecialCharsFromEntityFields($entityData, $fields);
        } else {
            $specialChars->replaceSpecialCharsFromEntityFields($entityData, $fields);
        }
    }
}
