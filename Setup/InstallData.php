<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Staempfli\CommerceImport\Model\Utils\Attribute\Attribute;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeSet;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeGroup;

/**
 * Install Data script
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    const MS3_GROUP_NAME = 'mS3 Commerce';
    /**
     * @var array $attributes to install
     */
    protected $attributes = [
        'ms3_import_category' => [
            'label' => 'Import Category',
            'type' => 'int',
            'input' => 'select',
            'input_renderer' => 'Magento\Config\Model\Config\Source\Yesno',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'default' => 0,
            'position' => 1,
            'note' => 'Define if this is the Base Category to store mediaSolution3 Products and Categories'
        ],
        'ms3_imported' => [
            'label' => 'Imported',
            'type' => 'int',
            'input' => 'select',
            'input_renderer' => 'Magento\Config\Model\Config\Source\Yesno',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'default' => 0,
            'position' => 2,
        ],
        'ms3_active' => [
            'label' => 'Active',
            'type' => 'int',
            'input' => 'select',
            'input_renderer' => 'Magento\Config\Model\Config\Source\Yesno',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'default' => 0,
            'position' => 3,
        ],
        'ms3_id' => [
            'label' => 'mS3 ID',
            'position' => 4,
        ],
        'ms3_guid' => [
            'label' => 'mS3 GUID',
            'position' => 5,
        ],
        'ms3_market_id' => [
            'label' => 'mS3 Market ID',
            'position' => 6,
        ],
        'ms3_lang_id' => [
            'label' => 'mS3 Langauge ID',
            'position' => 7,
        ],
    ];
    /**
     * @var Attribute
     */
    private $attribute;
    /**
     * @var AttributeGroup
     */
    private $attributeGroup;
    /**
     * @var AttributeSet
     */
    private $attributeSet;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * InstallData constructor.
     * @param Attribute $attribute
     * @param AttributeGroup $attributeGroup
     * @param AttributeSet $attributeSet
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        Attribute $attribute,
        AttributeGroup $attributeGroup,
        AttributeSet $attributeSet,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->attribute = $attribute;
        $this->attributeGroup = $attributeGroup;
        $this->attributeSet = $attributeSet;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) //@codingStandardsIgnoreLine
    {
        $this->updateAttributeSets(Product::ENTITY);
        $this->updateAttributeSets(Category::ENTITY);

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        foreach ($this->attributes as $code => $arr) {
            $data = array_replace(
                [
                    'required' => 0,
                    'user_defined' => 0,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'mS3 Commerce',
                    'searchable' => 0,
                    'comparable' => 0,
                    'visible_on_front' => 0,
                    'used_for_sort_by' => 0,
                    'used_in_product_listing' => 0,
                    'visible_in_advanced_search' => 0,
                ],
                $arr
            );

            $this->attribute->removeAttribute($code, Category::ENTITY);
            $this->attribute->removeAttribute($code, Product::ENTITY);

            $this->attribute->addAttribute($code, Category::ENTITY, $data);

            // @see: Magento\Eav\Model\ResourceModel\Entity\Attribute in line 187
            if (isset($data['source'])
                && $data['source'] === 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'
            ) {
                $this->updateAttributeSourceModel($eavSetup, $code, Category::ENTITY, $data['source']);
            }
            $this->assignAttribute(Category::ENTITY, $code, $data['position']);

            if ($code !== 'ms3_import_category') {
                $this->attribute->addAttribute($code, Product::ENTITY, $data);

                // @see: Magento\Eav\Model\ResourceModel\Entity\Attribute in line 187
                if (isset($data['source'])
                    && $data['source'] === 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'
                ) {
                    $this->updateAttributeSourceModel($eavSetup, $code, Product::ENTITY, $data['source']);
                }

                $this->assignAttribute(Product::ENTITY, $code, $data['position']);
            }
        }
    }

    /**
     * @param string $entity
     */
    protected function updateAttributeSets($entity = Product::ENTITY)
    {
        $sets = $this->attributeSet->getAttributeSetsByEntity($entity);
        foreach ($sets as $set) {
            /** @var $set \Magento\Eav\Model\Entity\Attribute\Set */
            $this->attributeGroup->addAttributeGroup($entity, $set->getAttributeSetId(), self::MS3_GROUP_NAME);
        }
    }

    /**
     * @param $entity
     * @param $code
     * @param int $position
     */
    protected function assignAttribute($entity, $code, $position = 0)
    {
        $sets = $this->attributeSet->getAttributeSetsByEntity($entity);
        /** @var $set \Magento\Eav\Model\Entity\Attribute\Set */
        foreach ($sets as $set) {
            $this->attribute
                ->assign($entity, $set->getAttributeSetName(), self::MS3_GROUP_NAME, $code, $position);
        }
    }

    /**
     * @param EavSetup $eavSetup
     * @param string $code
     * @param string $entity
     * @param string $sourceModel
     */
    protected function updateAttributeSourceModel(EavSetup $eavSetup, $code, $entity, $sourceModel)
    {
        $entityType = $this->attribute->getEntityType($entity);
        $eavSetup->updateAttribute($entityType->getEntityTypeId(), $code, 'source_model', $sourceModel);
    }
}
