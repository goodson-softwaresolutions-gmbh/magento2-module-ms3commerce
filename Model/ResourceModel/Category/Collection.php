<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\ResourceModel\Category;

use \Magento\Cms\Model\ResourceModel\AbstractCollection;

/**
 * Class Collection
 * @package Staempfli\commerceImport\Model\ResourceModel\Category
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Staempfli\CommerceImport\Model\Reader\Category',
            'Staempfli\CommerceImport\Model\ResourceModel\Category'
        );
    }

    /**
     * @param array|int|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        $this->performAddStoreFilter($store, $withAdmin);
        return $this;
    }

    /**
     * @return $this
     */
    public function addProductIds()
    {
        $categories = [];

        if ($this->getFlag('product_ids_added')) {
            return $this;
        }

        $ids = array_keys($this->_items);
        if (empty($ids)) {
            return $this;
        }

        $select = $this->getConnection()->select();
        $select->from(
            $this->getResource()->getTable('m2m_product_category'),
            ['id', 'product_id', 'category_id', 'sort']
        );
        $select->where('category_id IN (?)', $ids);
        $query = $this->getConnection()->query($select);

        while ($row = $query->fetch()) {
            $categories[$row['category_id']][$row['product_id']] = [
                'product_id' => $row['product_id'],
                'position' => $row['sort']
            ];
        }

        foreach ($this->_items as $item) {
            if (isset($categories[$item->getId()])) {
                $item->setProductIds($categories[$item->getId()]);
            } else {
                $item->setProductIds([]);
            }
        }

        $this->setFlag('product_ids_added', true);
        return $this;
    }
}
