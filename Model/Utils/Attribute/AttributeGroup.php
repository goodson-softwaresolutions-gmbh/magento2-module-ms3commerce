<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils\Attribute;

use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\Filter\Translit;

class AttributeGroup extends AbstractAttribute
{
    /**
     * @var AttributeGroupInterface
     */
    private $attributeGroupInterface;
    /**
     * @var AttributeGroupRepositoryInterface
     */
    private $attributeGroupRepositoryInterface;
    /**
     * @var Translit
     */
    private $translitFilter;

    /**
     * Group constructor.
     * @param AttributeGroupInterface $attributeGroupInterface
     * @param AttributeGroupRepositoryInterface $attributeGroupRepositoryInterface
     * @param Translit $translitFilter
     * @param EavSetup $eavSetup
     * @param EavConfig $eavConfig
     */
    public function __construct(
        AttributeGroupInterface $attributeGroupInterface,
        AttributeGroupRepositoryInterface $attributeGroupRepositoryInterface,
        Translit $translitFilter,
        EavSetup $eavSetup,
        EavConfig $eavConfig
    ) {
        parent::__construct($eavSetup, $eavConfig);
        $this->attributeGroupInterface = $attributeGroupInterface;
        $this->attributeGroupRepositoryInterface = $attributeGroupRepositoryInterface;
        $this->translitFilter = $translitFilter;
    }

    /**
     * @param string $entity
     * @param int $attributeSetId
     * @param $name
     */
    public function addAttributeGroup($entity = Product::ENTITY, $attributeSetId = 0, $name)
    {
        $entityType = $this->getEavConfig()->getEntityType($entity);

        if (!$attributeSetId) {
            $attributeSetId = $entityType->getDefaultAttributeSetId();
        }

        $attributeCode = $this->getAttributeGroupCodeFromName($name);
        $attributeGroup = $this->getEavSetup()
            ->getAttributeGroupByCode($entityType->getEntityTypeId(), $attributeSetId, $attributeCode);

        if (!$attributeGroup) {
            $group = $this->attributeGroupInterface;
            $group
                ->unsetData()
                ->setAttributeSetId($attributeSetId)
                ->setAttributeGroupCode($attributeCode)
                ->setAttributeGroupName($name);

            $this->attributeGroupRepositoryInterface->save($group);
        }
    }

    private function getAttributeGroupCodeFromName(string $name): string
    {
        return trim(
            preg_replace(
                '/[^a-z0-9]+/',
                '-',
                $this->translitFilter->filter(strtolower($name))
            ),
            '-'
        );
    }
}
