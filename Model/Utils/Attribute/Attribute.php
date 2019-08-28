<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils\Attribute;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute as EavAbstractAttribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Eav\Model\Entity\Setup\PropertyMapperInterface;
use Magento\Framework\App\ResourceConnection;
use Staempfli\CommerceImport\Model\Config;

/**
 * Class Attribute
 * @package Staempfli\CommerceImport\Model\Attribute
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attribute extends AbstractAttribute
{
    const IMPORT_OPTION_MAPPING_TABLE = 'commerce_import_option_mapping';
    /**
     * @var array
     */
    private $systemAttributes = [];
    /**
     * @var array
     */
    private $customAttributes = [];
    /**
     * @var array
     */
    private $mapping = [];
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepositoryInterface;
    /**
     * @var AttributeManagementInterface
     */
    private $attributeManagementInterface;
    /**
     * @var AttributeInterface
     */
    private $attributeInterface;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var PropertyMapperInterface
     */
    private $propertyMapperInterface;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var array
     */
    private $attributesInAttributeSet = [];
    /**
     * @var Config
     */
    private $config;

    /**
     * Attribute constructor.
     * @param AttributeRepositoryInterface $attributeRepositoryInterface
     * @param AttributeManagementInterface $attributeManagementInterface
     * @param AttributeInterface $attributeInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PropertyMapperInterface $propertyMapperInterface
     * @param ResourceConnection $resourceConnection
     * @param EavSetup $eavSetup
     * @param EavConfig $eavConfig
     * @param Config $config
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepositoryInterface,
        AttributeManagementInterface $attributeManagementInterface,
        AttributeInterface $attributeInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PropertyMapperInterface $propertyMapperInterface,
        ResourceConnection $resourceConnection,
        EavSetup $eavSetup,
        EavConfig $eavConfig,
        Config $config
    ) {
        parent::__construct($eavSetup, $eavConfig);
        $this->attributeRepositoryInterface = $attributeRepositoryInterface;
        $this->attributeManagementInterface = $attributeManagementInterface;
        $this->attributeInterface = $attributeInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->propertyMapperInterface = $propertyMapperInterface;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
    }

    /**
     * Get Entity attributes using specific filters
     *
     * @param $entity
     * @param array $filters
     * @return array
     */
    public function getEntityAttributesUsingFilters($entity, array $filters)
    {
        $attributesResult = [];

        foreach ($filters as $field => $value) {
            $this->searchCriteriaBuilder->addFilter($field, $value);
        }

        $attributes = $this->attributeRepositoryInterface->getList(
            $entity,
            $this->searchCriteriaBuilder->create()
        );

        foreach ($attributes->getItems() as $attribute) {
            $attributesResult[$attribute->getAttributeCode()] = $attribute;
        }

        return $attributesResult;
    }

    /**
     * @param $entity
     * @return mixed
     */
    public function getCustomAttributesByEntity($entity)
    {
        if ($this->customAttributes && isset($this->customAttributes[$entity])) {
            return $this->customAttributes[$entity];
        }

        $this->customAttributes[$entity] = $this->getEntityAttributesUsingFilters($entity, ['ms3_imported' => 1]);

        return $this->customAttributes[$entity];
    }

    /**
     * @param $entity
     * @return mixed
     */
    public function getSystemAttributesByEntity($entity)
    {
        if ($this->systemAttributes && isset($this->systemAttributes[$entity])) {
            return $this->systemAttributes[$entity];
        }

        $this->systemAttributes[$entity] = $this->getEntityAttributesUsingFilters($entity, ['ms3_imported' => 0]);

        return $this->systemAttributes[$entity];
    }

    /**
     * @param $code
     * @param string $entity
     * @return bool
     */
    public function isSystemAttribute($code, $entity = Product::ENTITY)
    {
        if (in_array($code, $this->getSystemAttributesByEntity($entity))) {
            return true;
        }
        return false;
    }

    /**
     * @param $code
     * @param string $entity
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addAttribute($code, $entity = Product::ENTITY, array $data = [])
    {
        $entityType = $this->getEntityType($entity);
        $attribute = $this->getAttribute($code, $entity);
        if ($attribute->getAttributeId()) {
            $data = array_merge($attribute->getData(), $data);
            $updatedAttribute = $this->updateExistingAttribute($attribute, $data);
            $this->saveAttributeOptionWithValues($updatedAttribute, $data);
        } else {
            $attributeData = array_replace(
                ['entity_type_id' => $entityType->getEntityTypeId(), 'attribute_code' => $code],
                $this->propertyMapperInterface->map($data, $entityType->getEntityTypeId())
            );
            $attribute = $this->attributeInterface;
            $attribute->unsetData();
            $attribute->setData($attributeData);
            $this->attributeRepositoryInterface->save($attribute);
            $updatedAttribute = $this->updateSourceModel($attribute, $attributeData);
            $this->saveAttributeOptionWithValues($updatedAttribute, $data);
        }
    }

    private function updateExistingAttribute(EavAbstractAttribute $attribute, array $data) : EavAbstractAttribute
    {
        if ($attribute->getFrontendInput() != $data['input']) {
            $this->getEavConfig()->clear();
        }
        if (!$this->isMaster($data)) {
            $data['label'] = $attribute->getDefaultFrontendLabel();
        }
        unset($data['option']);
        $this->getEavSetup()->addAttribute($attribute->getEntityTypeId(), $attribute->getAttributeCode(), $data);
        return $this->getEavConfig()->getAttribute($attribute->getEntityType(), $attribute->getAttributeCode());
    }

    /**
     * @param EavAbstractAttribute $attribute
     * @param array $data
     * @return bool
     */
    private function saveAttributeOptionWithValues(EavAbstractAttribute $attribute, array $data)
    {
        $storeId = $this->getStoreId($data);

        if (isset($data['label'])) {
            $this->setAttributeLabel($attribute, $data['label'], $storeId);
        }

        if (!isset($data['option'])
            || !isset($data['option']['values'])
            || !$attribute->usesSource()
        ) {
            return false;
        }

        foreach ($data['option']['values'] as $mappingId => $valueData) {
            $optionId = $this->getOptionId($mappingId, $attribute->getAttributeId());
            $attributeOptionData = [
                'attribute_id' => $attribute->getId(),
                'sort_order' => $valueData['sort_order'],
            ];

            if (!$optionId) {
                $optionId = $this->createAttributeOption($attributeOptionData);
            } else {
                $this->updateAttributeOption($optionId, $attributeOptionData);
            }
            if ($this->isMaster($data)) {
                $this->setAttributeOptionValues($optionId, $mappingId);
            }
            $this->setAttributeOptionValues($optionId, $valueData['value'], $storeId);
            $this->updateCommerceMappingTable($optionId, $attribute->getAttributeId(), $mappingId);
        }

        return true;
    }

    private function setAttributeLabel(EavAbstractAttribute $attribute, string $label, int $storeId = 0)
    {
        $optionLabelTable = $this->resourceConnection->getTableName('eav_attribute_label');
        $labelId = $this->resourceConnection->getConnection()->fetchOne(sprintf(
            'SELECT attribute_label_id FROM %s WHERE attribute_id = %s AND store_id = %d',
            $optionLabelTable,
            (int)$attribute->getId(),
            $storeId
        ));

        if ($labelId) {
            // Update
            $this->resourceConnection->getConnection()->update(
                $optionLabelTable,
                ['value' => $label],
                sprintf('attribute_label_id = %d', $labelId)
            );
        } else {
            // Insert
            $this->resourceConnection->getConnection()->insert(
                $optionLabelTable,
                [
                    'store_id' => $storeId,
                    'attribute_id' => $attribute->getId(),
                    'value' => $label
                ]
            );
        }
    }

    /**
     * @param $optionId
     * @param $value
     * @param int $storeId
     * @return bool
     */
    private function setAttributeOptionValues($optionId, $value, $storeId = 0)
    {
        if (!$optionId) {
            return false;
        }

        $optionValueTable = $this->resourceConnection->getTableName('eav_attribute_option_value');
        $valueId = $this->resourceConnection->getConnection()->fetchOne(
            sprintf(
                'SELECT value_id FROM %s WHERE option_id = %s AND store_id = %d',
                $optionValueTable,
                (int) $optionId,
                $storeId
            )
        );

        if ($valueId) {
            // Update
            $this->resourceConnection->getConnection()->update(
                $optionValueTable,
                ['value' => $value],
                sprintf('value_id = %d', $valueId)
            );
        } else {
            // Insert
            $this->resourceConnection->getConnection()->insert(
                $optionValueTable,
                [
                    'store_id' => $storeId,
                    'option_id' => $optionId,
                    'value' => $value
                ]
            );
            $this->resetCommerceMapping();
        }
        return true;
    }

    /**
     * @param string $code
     * @param string $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeAttribute($code, $entity = Product::ENTITY)
    {
        $attribute = $this->getAttribute($code, $entity);
        if ($attribute->getAttributeId()) {
            $this->attributeRepositoryInterface->delete($attribute);
        }
    }

    /**
     * @param $entity
     * @param $setName
     * @param $groupName
     * @param $code
     * @param int $sortOrder
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assign($entity, $setName, $groupName, $code, $sortOrder = 0)
    {
        $entityType = $this->getEntityType($entity);
        $attributeSetId = (int) $this->getEavSetup()->getAttributeSetId($entityType->getEntityTypeId(), $setName);
        $attributeGroupId = (int) $this->getEavSetup()
            ->getAttributeGroupId($entityType->getEntityTypeId(), $attributeSetId, $groupName);
        if ($this->config->attributeKeepMagentoSortOrder()) {
            $existingOrder = $this->getSortOrderInAttributeSet(
                $code,
                $entityType->getEntityTypeCode(),
                $attributeSetId
            );
            $sortOrder = ($existingOrder > 0) ? $existingOrder : $sortOrder;
        }
        $this->attributeManagementInterface
            ->assign($entityType->getEntityTypeCode(), $attributeSetId, $attributeGroupId, $code, $sortOrder);
    }

    /**
     * @param string $entity
     * @return \Magento\Eav\Model\Entity\Type
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEntityType($entity)
    {
        return $this->getEavConfig()->getEntityType($entity);
    }

    /**
     * @param string $code
     * @param string $entity
     * @return EavAbstractAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttribute($code, $entity = Product::ENTITY)
    {
        $entityType = $this->getEntityType($entity);
        return $this->getEavConfig()->getAttribute($entityType, $code);
    }

    private function getSortOrderInAttributeSet(string $attributeCode, string $entityTypeCode, int $attributeSetId): int
    {
        if (!isset($this->attributesInAttributeSet[$attributeSetId])) {
            $this->attributesInAttributeSet[$attributeSetId] = $this->attributeManagementInterface
                ->getAttributes($entityTypeCode, $attributeSetId);
        }

        $attribute = $this->getAttribute($attributeCode, $entityTypeCode);
        if ($attribute->getId() && isset($this->attributesInAttributeSet[$attributeSetId][$attribute->getId()])) {
            $attributeInSetData = $this->attributesInAttributeSet[$attributeSetId][$attribute->getId()];
            return $attributeInSetData->getSortOrder();
        }
        return 0;
    }

    /**
     * @param int $optionId
     * @param int $attributeId
     * @param string $mappingId
     */
    private function updateCommerceMappingTable(int $optionId, int $attributeId, string $mappingId)
    {
        $table = $this->resourceConnection->getTableName(self::IMPORT_OPTION_MAPPING_TABLE);
        $this->resourceConnection->getConnection()->delete($table, 'option_id = ' . $optionId);
        $this->resourceConnection->getConnection()->insert(
            $table,
            [
                'option_id' => $optionId,
                'mapping_id' => $mappingId,
                'attribute_id' => $attributeId
            ]
        );
    }

    /**
     * @return array
     */
    private function getCommerceMapping()
    {
        if ($this->mapping) {
            return $this->mapping;
        }
        $table = $this->resourceConnection->getTableName(self::IMPORT_OPTION_MAPPING_TABLE);
        $result = $this->resourceConnection
            ->getConnection()->fetchAll(sprintf('SELECT mapping_id, option_id, attribute_id FROM %s', $table));
        foreach ($result as $row) {
            $this->mapping[$row['mapping_id']][$row['attribute_id']] = $row['option_id'];
        }
        return $this->mapping;
    }

    /**
     * @return $this
     */
    private function resetCommerceMapping()
    {
        $this->mapping = [];
        return $this;
    }

    /**
     * @param string $mappingId
     * @param int $attributeId
     * @return int
     */
    private function getOptionId(string $mappingId, int $attributeId)
    {
        $mapping = $this->getCommerceMapping();
        return isset($mapping[$mappingId][$attributeId]) ? (int) $mapping[$mappingId][$attributeId] : 0;
    }

    /**
     * Create Attribute Option
     *
     * @param array $attributeOptionData
     * @return int $optionId
     */
    private function createAttributeOption($attributeOptionData)
    {
        $optionTable = $this->resourceConnection->getTableName('eav_attribute_option');
        $this->resourceConnection->getConnection()->insert($optionTable, $attributeOptionData);
        $optionId = $this->resourceConnection->getConnection()->lastInsertId($optionTable);
        return (int) $optionId;
    }

    /**
     * Update Attribute option
     *
     * @param $optionId
     * @param array $attributeOptionData
     * @return int
     */
    private function updateAttributeOption($optionId, array $attributeOptionData)
    {
        $optionTable = $this->resourceConnection->getTableName('eav_attribute_option');
        return $this->resourceConnection->getConnection()
            ->update($optionTable, $attributeOptionData, ['option_id = ?' => (int)$optionId]);
    }

    /**
     * @param AttributeInterface $attribute
     * @param array $attributeData
     * @return EavAbstractAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateSourceModel(AttributeInterface $attribute, array $attributeData)
    {
        if (isset($attributeData['source_model'])) {
            if ($attributeData['source_model'] !== $attribute->getSourceModel()) {
                $this->getEavSetup()->updateAttribute(
                    $attribute->getEntityTypeId(),
                    $attribute->getAttributeCode(),
                    'source_model',
                    $attributeData['source_model']
                );
            }
        }
        return $this->getEavConfig()
            ->getAttribute($attribute->getEntityType(), $attribute->getAttributeCode());
    }

    private function getStoreId(array $data) : int
    {
        $storeId = 0;
        if (isset($data['_store_id'])) {
            $storeId = (int) $data['_store_id'];
        }
        return $storeId;
    }

    private function isMaster(array $data) : bool
    {
        if (isset($data['_is_master'])) {
            return $data['_is_master'];
        }
        return false;
    }
}
