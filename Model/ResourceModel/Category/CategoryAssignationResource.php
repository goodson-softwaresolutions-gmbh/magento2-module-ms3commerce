<?php
/**
 * CategoryAssignationResource
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\ResourceModel\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\DB\Select;
use Staempfli\CommerceImport\Model\Utils\Store;
use Staempfli\CommerceImport\Model\Utils\StoreFactory;
use Magento\Framework\App\DeploymentConfig;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

/**
 * Class CategoryAssignation
 * @package Staempfli\CommerceImport\Model\Import\Product
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryAssignationResource
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $magentoConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $importConnection;
    /**
     * @var Store
     */
    private $store;
    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var StoreFactory
     */
    private $storeFactory;
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;
    /**
     * @var array
     */
    private $productMsIdCategoryIdPairs = [];

    public function __construct(
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig,
        StoreFactory $storeFactory,
        DeploymentConfig $deploymentConfig
    ) {
        $this->magentoConnection = $resourceConnection->getConnection();
        $this->importConnection = $resourceConnection->getConnection(CommerceImportSetupConfig::DB_CONNECTION_SETUP);
        $this->eavConfig = $eavConfig;
        $this->storeFactory = $storeFactory;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * @param int $batchPage
     * @param int $batchLimit
     * @return array
     */
    public function getImportAssignationIds($batchPage, $batchLimit)
    {
        $select = $this->importConnection->select()
            ->from($this->importConnection->getTableName('m2m_product_category'), 'id')
            ->limitPage($batchPage, $batchLimit);
        return $this->importConnection->fetchCol($select);
    }

    public function reset()
    {
        $this->productMsIdCategoryIdPairs = [];
    }

    /**
     * @param int $productMsId
     * @param \Magento\Store\Model\Store|null $store
     * @return array
     */
    public function getCategoryIdsFromProductMsId($productMsId, $store = null)
    {
        if ($store === null) {
            $store = $this->getStore()->getCurrentStore();
        }
        if (!$this->productMsIdCategoryIdPairs) {
            $select = $this->magentoConnection->select()
                ->from(
                    ['m2m_prod_cat' => $this->importConnection->getTableName('m2m_product_category')],
                    'product_id as product_ms_id',
                    $this->getDbSchemaName(CommerceImportSetupConfig::DB_CONNECTION_SETUP)
                )
                ->joinInner(
                    ['m2m_cat' => $this->importConnection->getTableName('m2m_category')],
                    'm2m_cat.id = m2m_prod_cat.category_id',
                    '',
                    $this->getDbSchemaName(CommerceImportSetupConfig::DB_CONNECTION_SETUP)
                )
                ->joinLeft(
                    ['mag_cat' => $this->magentoConnection->getTableName('catalog_category_entity_varchar')],
                    "mag_cat.value = m2m_cat.guid",
                    ['category_ids' => new \Zend_Db_Expr(sprintf('GROUP_CONCAT(DISTINCT entity_id SEPARATOR ",")'))]
                )
                ->where("mag_cat.attribute_id = ?", $this->getMs3AttributeGuid(Category::ENTITY))
                ->where("mag_cat.store_id = ?", $store->getId())
                ->group(['product_ms_id']);
            $this->productMsIdCategoryIdPairs = $this->magentoConnection->fetchPairs($select);
        }

        if (isset($this->productMsIdCategoryIdPairs[$productMsId])) {
            return explode(',', $this->productMsIdCategoryIdPairs[$productMsId]);
        }

        return [];
    }

    /**
     * @param array $productIds
     * @return int
     */
    public function deleteProductAssignations(array $productIds)
    {
        $table = $this->magentoConnection->getTableName('catalog_category_product');
        return $this->magentoConnection->delete($table, ['product_id IN (?)' => $productIds]);
    }

    /**
     * @param array $assignationIds
     * @return \Zend_Db_Statement_Interface
     */
    public function updateCategoryAssignations(array $assignationIds)
    {
        $table = ['mag_prod_cat' => $this->magentoConnection->getTableName('catalog_category_product')];
        $updateSelect = $this->getProductCategoryAssignationSelect($assignationIds)
            ->where('mag_prod_cat.category_id = mag_cat.entity_id')
            ->where('mag_prod_cat.product_id = mag_prod.entity_id');

        $updateQuery = $updateSelect->crossUpdateFromSelect($table);
        return $this->magentoConnection->query($updateQuery);
    }

    private function getProductCategoryAssignationSelect(array $assignationIds): Select
    {
        $select = $this->magentoConnection->select()
            ->from(
                ['m2m_prod_cat' => $this->importConnection->getTableName('m2m_product_category')],
                'sort as position',
                $this->getDbSchemaName(CommerceImportSetupConfig::DB_CONNECTION_SETUP)
            )
            ->joinLeft(
                ['mag_cat' => $this->magentoConnection->getTableName('catalog_category_entity_varchar')],
                "mag_cat.value = m2m_prod_cat.category_id",
                'entity_id as category_id'
            )
            ->joinLeft(
                ['mag_prod' => $this->magentoConnection->getTableName('catalog_product_entity_varchar')],
                "mag_prod.value = m2m_prod_cat.product_id",
                'entity_id as product_id'
            )
            ->where("mag_cat.attribute_id = ?", $this->getMs3AttributeId(Category::ENTITY))
            ->where("mag_prod.attribute_id = ?", $this->getMs3AttributeId(Product::ENTITY))
            ->where("mag_cat.store_id = ?", $this->getStore()->getCurrentStore()->getId())
            ->where("mag_prod.store_id = ?", $this->getStore()->getStoreIdCheckingMaster())
            ->where("m2m_prod_cat.id IN (?)", $assignationIds);
        return $select;
    }

    private function getMs3AttributeId(string $entityType): string
    {
        $query = $this->magentoConnection->select()
            ->from(['eav_att' => $this->magentoConnection->getTableName('eav_attribute')], 'attribute_id')
            ->where('eav_att.entity_type_id = ?', $this->eavConfig->getEntityType($entityType)->getId())
            ->where('eav_att.attribute_code = ?', 'ms3_id');

        return $this->magentoConnection->fetchOne($query);
    }

    private function getMs3AttributeGuid(string $entityType): string
    {
        $query = $this->magentoConnection->select()
            ->from(['eav_att' => $this->magentoConnection->getTableName('eav_attribute')], 'attribute_id')
            ->where('eav_att.entity_type_id = ?', $this->eavConfig->getEntityType($entityType)->getId())
            ->where('eav_att.attribute_code = ?', 'ms3_guid');

        return $this->magentoConnection->fetchOne($query);
    }

    /**
     * @param string $resourceName
     * @return bool|string
     */
    private function getDbSchemaName($resourceName = ResourceConnection::DEFAULT_CONNECTION): string
    {
        $config = $this->deploymentConfig
            ->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS . '/' . $resourceName);
        return $config[ConfigOptionsListConstants::KEY_NAME]??'';
    }

    private function getStore(): Store
    {
        if (!$this->store) {
            $this->store = $this->storeFactory->create();
        }
        return $this->store;
    }
}
