<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Reader;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Staempfli\CommerceImport\Api\Data\AttributeReaderInterface;
use Staempfli\CommerceImport\Model\AbstractReader;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Staempfli\CommerceImport\Model\ResourceModel\Attribute\Collection as AttributeCollection;

/**
 * Class Attribute
 * @package Staempfli\CommerceImport\Model\Reader
 */
class Attribute extends AbstractReader implements AttributeReaderInterface
{
    const ENTITY = 'attribute';

    /**
     * @var string
     */
    protected $entity = self::ENTITY;
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $attributeEntityMapping = [
        parent::ATTRIBUTE_ENTITY_CATEGORY => Category::ENTITY,
        parent::ATTRIBUTE_ENTITY_PRODUCT => Product::ENTITY,
    ];
    /**
     * @var array
     */
    protected $attributeScopeMapping = [
        parent::ATTRIBUTE_SCOPE_GLOBAL => ScopedAttributeInterface::SCOPE_GLOBAL,
        parent::ATTRIBUTE_SCOPE_WEBSITE => ScopedAttributeInterface::SCOPE_WEBSITE,
        parent::ATTRIBUTE_SCOPE_STORE => ScopedAttributeInterface::SCOPE_STORE
    ];
    /**
     * @var array
     */
    protected $attributeFieldTypeMapping = [
        parent::ATTRIBUTE_TYPE_TEXT => [
            'type' => 'varchar',
            'input' => 'text'
        ],
        parent::ATTRIBUTE_TYPE_TEXTAREA => [
            'type' => 'text',
            'input' => 'textarea'
        ],
        parent::ATTRIBUTE_TYPE_DATE => [
            'type' => 'datetime',
            'input' => 'date'
        ],
        parent::ATTRIBUTE_TYPE_BOOLEAN => [
            'type' => 'int',
            'input' => 'select',
            'default' => 0,
            'input_renderer' => 'Magento\Config\Model\Config\Source\Yesno',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'is_html_allowed_on_front' => 0,
        ],
        parent::ATTRIBUTE_TYPE_SELECT => [
            'type' => 'int',
            'input' => 'select',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table'
        ],
        parent::ATTRIBUTE_TYPE_MULITSELECT => [
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            'type' => 'varchar',
            'input' => 'multiselect',
        ]
    ];

    /**
     * @var array
     */
    protected $attributeDefaultData = [
        'label' => '',
        'group' => 'mS3 Commerce',
        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
        'searchable' => 0,
        'comparable' => 0,
        'visible_on_front' => 0,
        'position' => 0,
        'used_for_sort_by' => 0,
        'used_in_product_listing' => 0,
        'visible_in_advanced_search' => 0,
        'is_html_allowed_on_front' => 1,
        'user_defined' => 1,
        'required' => 0,
        'ms3_imported' => 1,
    ];

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Staempfli\CommerceImport\Model\ResourceModel\Attribute');
    }

    /**
     * @return array
     */
    public function fetch()
    {
        /** @var $collection AttributeCollection */
        $collection = $this->getCollection()
            ->addFilter('market_id', $this->marketId)
            ->addFilter('lang_id', $this->langId)
            ->setOrder('main_table.group')
            ->setOrder('main_table.sort', 'asc');

        $collection->addAttributeSets();
        $collection->addAttributeOptions();

        $this->prepareAttributes($collection);

        return $this->attributes;
    }

    /**
     * @param string $entityType
     * @param string $code
     * @param array $attribute
     */
    public function addAttribute(string $entityType, string $code, array $attribute)
    {
        if (in_array($entityType, $this->attributeEntityMapping)) {
            $this->attributes[$entityType][$code]['_data'] = $attribute;
        }
    }

    /**
     * @param AbstractCollection $collection
     * @return array
     */
    protected function prepareAttributes(AttributeCollection $collection)
    {
        $this->getReaderUtils()->getConsoleOutput()->startProgress($collection->getSize());
        $this->attributes = [];
        foreach ($collection as $item) {
            $this->getReaderUtils()->getConsoleOutput()->advanceProgress();
            $entityType = $this->getEntityTypeById($item->getEntityType());
            $scopeId = $this->getScopeById($item->getScope());
            $label = ($item->getTitle()) ? $item->getTitle() : $item->getCode();

            if (!$this->isExistingCustomAttribute($entityType, $item->getCode())
                && !$this->isStructureMaster()
            ) {
                continue;
            }

            $attribute = $this->attributeDefaultData;

            // Check if attribute has a unit
            $label = $this->addUnit($item, $label);

            if ($item->getAttributeOptions()) {
                $attribute = $this->addAttributeOptions($attribute, $item->getAttributeOptions());
            }

            $attribute = $this->addFieldTypeData($attribute, $item->getFieldType());

            $attribute['filterable'] = $item->getFilterType();
            $attribute['label'] = $label;
            $attribute['global'] = $scopeId;
            $attribute['searchable'] = $item->getIsSearchable();
            $attribute['comparable'] = $item->getIsComparable();
            $attribute['visible_on_front'] = $item->getIsFrontend();
            $attribute['position'] = $item->getSort();
            $attribute['used_for_sort_by'] = $item->getIsSorting();
            $attribute['used_in_product_listing'] = $item->getIsListing();
            $attribute['visible_in_advanced_search'] = $item->getIsAdvancedSearch();
            $attribute['_store_id'] = $this->getStore()->getId();
            $attribute['_is_master'] = $this->isStructureMaster();

            if ($entityType === Product::ENTITY) {
                $this->attributes[$entityType][$item->getCode()]['_sets'] = $item->getAttributeSets();
                unset($attribute['group']);
                unset($attribute['position']);
            }
            $this->addAttribute($entityType, $item->getCode(), $attribute);
        }
        $this->getReaderUtils()->getConsoleOutput()->finishProgress();
        return $this->attributes;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    protected function getEntityTypeById($id)
    {
        if (!isset($this->attributeEntityMapping[$id])) {
            throw new \Exception('Entity Type not assigned to mapping: ' . $id);
        }
        return $this->attributeEntityMapping[$id];
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    protected function getScopeById($id)
    {
        if (!isset($this->attributeScopeMapping[$id])) {
            throw new \Exception('Scope not assigned to mapping: ' . $id);
        }
        return $this->attributeScopeMapping[$id];
    }

    /**
     * @param array $attribute
     * @param int $fieldType
     * @return array
     */
    protected function addFieldTypeData(array $attribute, $fieldType = 0)
    {
        if (isset($this->attributeFieldTypeMapping[$fieldType])) {
            $attribute = array_merge($attribute, $this->attributeFieldTypeMapping[$fieldType]);
        }
        return $attribute;
    }

    /**
     * @param array $attribute
     * @param array $options
     * @return array
     */
    protected function addAttributeOptions(array $attribute, array $options = [])
    {
        $attribute['option'] = [
            'values' => $options
        ];

        return $attribute;
    }

    /**
     * @param $entity
     * @param $code
     * @return bool
     */
    protected function isExistingCustomAttribute($entity, $code)
    {
        $attributes = $this->getReaderUtils()->getAttributeUtils()->getCustomAttributesByEntity($entity);
        return in_array($code, array_keys($attributes));
    }

    /**
     * @param $item
     * @param $label
     * @return string
     */
    protected function addUnit($item, $label)
    {
        if (!empty($item->getUnit())) {
            $label .= sprintf(' (%s)', $item->getUnit());
        }
        return $label;
    }
}
