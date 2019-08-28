<?php
/**
 * AbstractUrlKey
 *
 * @copyright Copyright (c) 2017 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\UrlKey;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Filter\FilterManager;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;

abstract class AbstractUrlKey
{
    protected $filterManager;
    protected $resourceConnection;
    protected $eavConfig;
    protected $entityType;

    protected $uniqueIdentifier;
    protected $uniqueIdentifiers = [];
    protected $urlKey;
    protected $urlKeys = [];
    protected $processedUrlKeys = [];

    protected $entityAttributeIds = [
        Category::ENTITY => [],
        Product::ENTITY => []
    ];
    protected $entitiesToCheck = [
        Category::ENTITY,
        Product::ENTITY
    ];

    public function __construct(
        FilterManager $filterManager,
        ResourceConnection $resourceConnection,
        EavConfig $eavConfig
    ) {
        $this->filterManager = $filterManager;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->entityType = $this->getEntityType();
    }

    abstract protected function getEntityType();

    public function getUniqueFormattedUrlKey(string $uniqueIdentifier, string $urlKey) : string
    {
        $this->uniqueIdentifier = $uniqueIdentifier;
        $formattedUrlKey = $this->filterManager->translitUrl($urlKey);
        $this->setUniqueUrlKey($formattedUrlKey);
        $this->updateProcessedUrlKeyList();
        return $this->urlKey;
    }

    protected function setUniqueUrlKey(string $urlKey, int $incrementId = 0)
    {
        $this->urlKey = ($incrementId) ? $urlKey.'-'.$incrementId : $urlKey;

        if (!$this->isUniqueUrlKey()) {
            $this->setUniqueUrlKey($urlKey, $incrementId+1);
        }
    }

    protected function isUniqueUrlKey() : bool
    {
        if ($this->existAnotherEntityWithSameUrlKey()) {
            return false;
        }
        if (in_array($this->urlKey, $this->processedUrlKeys)) {
            return false;
        }
        return true;
    }

    protected function existAnotherEntityWithSameUrlKey() : bool
    {
        foreach ($this->entitiesToCheck as $entityType) {
            if ($this->existUrlKeyOnEntity($entityType)) {
                return true;
            }
        }
        return false;
    }

    protected function existUrlKeyOnEntity(string $entityType)
    {
        $this->loadExistingUrlKeysAndIdentifiers($entityType);
        $urlEntityIds = $this->urlKeys[$entityType][$this->urlKey] ?? null;
        if ($urlEntityIds) {
            $urlEntityIds = array_unique(explode(',', $urlEntityIds));
            $uniqueIdentifierEntityId = $this->uniqueIdentifiers[$entityType][$this->uniqueIdentifier] ?? null;
            foreach ($urlEntityIds as $urlEntityId) {
                if ($urlEntityId !== $uniqueIdentifierEntityId) {
                    return true;
                }
            }
        }
        return false;
    }

    private function loadExistingUrlKeysAndIdentifiers(string $entityType)
    {
        if (!isset($this->urlKeys[$entityType])) {
            $this->urlKeys[$entityType] = $this->getAttributeValueAndIdPairs($entityType, 'url_key');
        }
        if (!isset($this->uniqueIdentifiers[$entityType])) {
            $this->uniqueIdentifiers[$entityType] = $this->getUniqueIdentifiersAndIdPairs();
        }
    }

    /**
     * Array pairs [uniqueIdentifier => id]
     *
     * @return array
     */
    abstract protected function getUniqueIdentifiersAndIdPairs(): array;

    private function getAttributeValueAndIdPairs(string $entityType, string $attributeCode)
    {
        $attributeId = $this->getAttributeId($entityType, $attributeCode);
        $varcharTable = $this->resourceConnection->getTableName(sprintf('%s_entity_varchar', $entityType));
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from(['eav_varchar' => $varcharTable], [
                'value',
                'entity_id' => new \Zend_Db_Expr('GROUP_CONCAT(entity_id SEPARATOR ",")')
            ])
            ->where('eav_varchar.attribute_id = ?', $attributeId)
            ->group(['value']);
        return $connection->fetchPairs($query);
    }

    protected function getAttributeId(string $entityType, string $attributeCode) : int
    {
        if (!isset($this->entityAttributeIds[$entityType][$attributeCode])) {
            $connection = $this->resourceConnection->getConnection();
            $query = $connection->select()
                ->from(['eav_att' => $this->resourceConnection->getTableName('eav_attribute')], 'attribute_id')
                ->where('eav_att.entity_type_id = ?', $this->eavConfig->getEntityType($entityType)->getId())
                ->where('eav_att.attribute_code = ?', $attributeCode);

            $this->entityAttributeIds[$entityType][$attributeCode] = $connection->fetchOne($query);
        }
        return (int) $this->entityAttributeIds[$entityType][$attributeCode];
    }

    protected function updateProcessedUrlKeyList()
    {
        $this->processedUrlKeys[] = $this->urlKey;
    }
}
