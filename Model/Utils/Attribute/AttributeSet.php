<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils\Attribute;

use Magento\Eav\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Eav\Api\Data\AttributeSetInterface;

class AttributeSet extends AbstractAttribute
{
    /**
     * @var AttributeSetInterface
     */
    private $attributeSetInterface;
    /**
     * @var AttributeSetManagementInterface
     */
    private $attributeSetManagementInterface;
    /**
     * @var AttributeSetRepositoryInterface
     */
    private $attributeSetRepositoryInterface;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Set constructor.
     * @param AttributeSetInterface $attributeSetInterface
     * @param AttributeSetManagementInterface $attributeSetManagementInterface
     * @param AttributeSetRepositoryInterface $attributeSetRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param EavSetup $eavSetup
     * @param EavConfig $eavConfig
     */
    public function __construct(
        AttributeSetInterface $attributeSetInterface,
        AttributeSetManagementInterface $attributeSetManagementInterface,
        AttributeSetRepositoryInterface $attributeSetRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        EavSetup $eavSetup,
        EavConfig $eavConfig
    ) {
        parent::__construct($eavSetup, $eavConfig);
        $this->attributeSetInterface = $attributeSetInterface;
        $this->attributeSetManagementInterface = $attributeSetManagementInterface;
        $this->attributeSetRepositoryInterface = $attributeSetRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $entity
     * @param string $name
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addAttributeSet(string $entity, string $name)
    {
        $entityType = $this->getEavConfig()->getEntityType($entity);
        $defaultAttributeSetId = $entityType->getDefaultAttributeSetId();

        $set = $this->attributeSetInterface;

        $set->unsetData()
            ->setEntityTypeId($entityType->getEntityTypeId())
            ->setAttributeSetName($name);

        $this->attributeSetManagementInterface->create($entity, $set, $defaultAttributeSetId);
    }

    /**
     * @param string $entity
     * @param string $name
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeSetId(string $entity, string $name)
    {
        $entityType = $this->getEavConfig()->getEntityType($entity);
        return $this->getEavSetup()->getAttributeSetId($entityType->getEntityTypeId(), $name);
    }

    /**
     * @param string $entity
     * @return AttributeSetInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeSetsByEntity(string $entity)
    {
        $attributeSets = $this->attributeSetRepositoryInterface->getList(
            $this->searchCriteriaBuilder->addFilter('entity_type_code', $entity)->create()
        );

        return $attributeSets->getItems();
    }
}
