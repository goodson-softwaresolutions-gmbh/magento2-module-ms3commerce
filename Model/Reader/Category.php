<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Reader;

use Staempfli\CommerceImport\Api\Data\CategoryReaderInterface;
use Staempfli\CommerceImport\Model\AbstractReader;

/**
 * Class Category
 * @package Staempfli\CommerceImport\Model\Reader
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class Category extends AbstractReader implements CategoryReaderInterface
{
    /**
     * @var array
     */
    protected $categories = [];
    /**
     * @var array
     */
    protected $existingMSCategories = [];

    // @codingStandardsIgnoreStart
    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Staempfli\CommerceImport\Model\ResourceModel\Category');
    }
    // @codingStandardsIgnoreEnd

    public function clearInstance()
    {
        $this->categories = [];
        $this->existingMSCategories = [];
        return parent::clearInstance();
    }

    /**
     * @return array
     */
    public function fetch()
    {
        $this->validateData();
        $this->prepareCategories();
        return $this->categories;
    }

    /**
     * @param array $category
     */
    public function addCategory(array $category)
    {
        $this->categories[] = $category;
    }

    protected function validateData()
    {
        if (!$this->isStructureMaster() && count($this->getExistingMSCategoryGuids()) === 0) {
            throw new \Exception('No existing Categories found, please import Master first!');
        }
    }

    protected function getExistingMSCategoryGuids()
    {
        if (!$this->existingMSCategories) {
            $categories = $this->getReaderUtils()->getCategoryUtils()
                ->getCollection()
                ->addFieldToFilter('ms3_imported', 1)
                ->addFieldToSelect('ms3_guid');

            foreach ($categories as $category) {
                $this->existingMSCategories[] = $category->getData('ms3_guid');
            }
        }
        return $this->existingMSCategories;
    }

    protected function prepareCategories()
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('level', ['gt' => 0])
            ->addFilter('market_id', $this->marketId)
            ->addFilter('lang_id', $this->langId)
            ->setOrder('level', 'asc');
        $this->getReaderUtils()->getConsoleOutput()->startProgress($collection->getSize());
        foreach ($collection as $item) {
            $this->getReaderUtils()->getConsoleOutput()->advanceProgress();
            $category = $this->getReaderUtils()
                ->getMapper()
                ->map($item->getData(), \Magento\Catalog\Model\Category::ENTITY, $this->getStore());
            $this->getReaderUtils()
                ->getEventManager()
                ->dispatch('reader_prepare_category_before', ['category' => $category]);
            $this->validateCategory($category);
            if (!$this->shouldAddCategory($category)) {
                $this->getReaderUtils()->getConsoleOutput()
                    ->comment(sprintf('Category not added [%s], Master Data not found.', $category['ms3_id']));
                continue;
            }
            $this->handleSpecialChars($category, ['name']);
            $this->setUniqueUrlKey($category);
            $this->setMs3Parent($category);
            $this->setCustomAttributes($category);
            $this->getReaderUtils()
                ->getEventManager()
                ->dispatch('reader_prepare_category_after', ['category' => $category]);
            $this->addCategory($category);
        }
        $this->getReaderUtils()->getConsoleOutput()->finishProgress();
    }

    protected function validateCategory($category)
    {
        if (!isset($category['name']) || empty($category['name'])) {
            throw new \Exception(sprintf('Category name not set [ms3_id: %s]', $category['ms3_id']));
        }

        if (stripos(
            $category['name'],
            $this->getReaderUtils()->getConfig()->getMultipleValuesSeparator()
        ) !== false
        ) {
            throw new \Exception(
                sprintf('Category name contains multiple values separator [ms3_id: %s]', $category['ms3_id'])
            );
        }
    }

    protected function shouldAddCategory(array $category)
    {
        if (!$this->isStructureMaster() && !in_array($category['ms3_guid'], $this->getExistingMSCategoryGuids())) {
            return false;
        }
        return true;
    }

    protected function setMs3Parent(array &$categoryData)
    {
        if ((int) $categoryData['ms3_level'] === 1) {
            $rootCategory = $this->getReaderUtils()->getCategoryUtils()->getImportCategoryByStore($this->getStore());
            $categoryData['ms3_parent'] = $rootCategory->getId();
        }
    }

    protected function setUniqueUrlKey(array &$categoryData)
    {
        if (!isset($categoryData['url_key']) || is_null($categoryData['url_key'])) { //@codingStandardsIgnoreLine
            $categoryData['url_key'] = $categoryData['name'];
        }
        $categoryData['url_key'] = $this->getReaderUtils()->getCategoryUrlKey()
            ->getUniqueFormattedUrlKey($categoryData['ms3_guid'], $categoryData['url_key']);
    }

    protected function setCustomAttributes(array &$categoryData)
    {
        if ($this->getResource()->hasCategoryAttributes()) {
            $attributes = $this->getResource()->getCategoryAttributes($categoryData['ms3_id'], $categoryData['ms3_market_id'], $categoryData['ms3_lang_id']);
            $this->handleSpecialChars($attributes, array_keys($attributes));
            foreach ($attributes as $code => $value) {
                $categoryData[$code] = $value;
            }
        }
    }
}
