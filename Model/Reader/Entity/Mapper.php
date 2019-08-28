<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Reader\Entity;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Api\Data\StoreInterface;

class Mapper
{
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var array
     */
    protected $productTypeMapping = [
        0 => Type::TYPE_SIMPLE,
        1 => Grouped::TYPE_CODE,
        2 => Configurable::TYPE_CODE,
        3 => Type::TYPE_VIRTUAL
    ];

    /**
     * @param array $input
     * @param $entityType
     * @param StoreInterface
     * @return array
     */
    public function map(array $input, $entityType, $store)
    {
        $this->data = $input;
        $this->addDefaultMapping();

        switch ($entityType) {
            case Category::ENTITY:
                $this->mapValue('ms3_imported', 'imported', 1);
                $this->mapValue('ms3_active', 'active', 1);
                $this->mapValue('ms3_level', 'level');
                $this->mapValue('ms3_parent', 'parent');
                break;
            case Product::ENTITY:
                $this->mapValue('ms3_imported', 'imported', (string)__('Yes'));
                $this->mapValue('ms3_active', 'active', (string)__('Yes'));
                $this->mapValue('attribute_set_code', 'attribute_set_name');
                $this->mapValue('product_online', 'online', 1);
                $this->mapValue('visibility', 'visibility', (string)__('Catalog, Search'));
                $this->mapProductBaseImage();
                $this->mapProductType();
                break;
        }

        return $this->data;
    }

    /**
     * @param $replace
     * @param $key
     * @param null $default
     */
    protected function mapValue($replace, $key, $default = null)
    {
        if (isset($this->data[$key])) {
            $this->data[$replace] = is_bool($this->data[$key])?(int)$this->data[$key]:$this->data[$key];
            if ($replace != $key) {
                unset($this->data[$key]);
            }
        } else {
            $this->data[$replace] = $default;
        }
    }

    protected function addDefaultMapping()
    {
        $this->mapValue('ms3_id', 'id');
        $this->mapValue('ms3_guid', 'guid');
        $this->mapValue('ms3_market_id', 'market_id');
        $this->mapValue('ms3_lang_id', 'lang_id');
    }

    /**
     * @throws \Exception
     */
    protected function mapProductType()
    {
        if (isset($this->productTypeMapping[$this->data['type']])) {
            $this->data['type'] = $this->productTypeMapping[$this->data['type']];
            $this->mapValue('product_type', 'type');
        } else {
            throw new \Exception(sprintf('Product type mapping not found, submitted [%s]', $this->data['type']));
        }
    }

    protected function mapProductBaseImage()
    {
        $this->mapValue('base_image', 'image_1');
        $this->data['small_image'] = $this->data['base_image'];
        $this->data['thumbnail_image'] = $this->data['base_image'];
        $this->data['swatch_image'] = $this->data['base_image'];
        $this->data['additional_images'] = $this->data['base_image'];

        $this->mapValue('base_image_label', 'image_1_label', '');
        $this->data['small_image_label'] = $this->data['base_image_label'];
        $this->data['thumbnail_image_label'] = $this->data['base_image_label'];
        $this->data['swatch_image_label'] = $this->data['base_image_label'];
        $this->data['additional_image_labels'] = $this->data['base_image_label'];
    }
}
