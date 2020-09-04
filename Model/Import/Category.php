<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Import;

use Magento\Store\Model\StoreManagerInterface;
use Staempfli\CommerceImport\Api\Data\CategoryImportInterface;
use Staempfli\CommerceImport\Model\Reader\Category as CategoryReader;
use Staempfli\CommerceImport\Model\AbstractImport;
use Staempfli\CommerceImport\Model\Utils\Import as ImportUtils;

class Category extends AbstractImport implements CategoryImportInterface
{
    /**
     * @var array
     */
    protected $categories = [];
    /**
     * @var array
     */
    protected $categoryCache = [];
    /**
     * @var CategoryReader
     */
    private $categoryReader;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Category constructor.
     * @param CategoryReader $categoryReader
     * @param ImportUtils $importUtils
     */
    public function __construct(
        CategoryReader $categoryReader,
        ImportUtils $importUtils,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($importUtils);
        $this->categoryReader = $categoryReader;
        $this->storeManager = $storeManager;
    }

    /**
     * Set categories data to import
     */
    public function prepare()
    {
        $this->categories = $this->categoryReader->fetch();
    }

    /**
     * Validate data before import
     *
     * @throws \Exception
     */
    public function validate()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Validate categories for store ' . $this->getStoreId());
        $categoriesToImport = count($this->categories);
        if ($categoriesToImport == 0) {
            throw new \Exception('No Categories to import');
        }
        $this->getImportUtils()->getConsoleOutput()->info(sprintf('%d valid categories', $categoriesToImport));
    }

    /**
     * Import Categories
     */
    public function import()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Import categories for store ' . $this->getStoreId());
        $this->markCategoriesAsInactive();
        $this->getImportUtils()->getConsoleOutput()->startProgress(count($this->categories));
        foreach ($this->categories as $category) {
            if ($this->getImportUtils()->getConfig()->isCategorySkipActive()) {
                if ($category['ms3_level'] > $this->getImportUtils()->getConfig()->getCategorySkipLevel()) {
                    continue;
                }
            }

            $parent = $category['ms3_parent'];
            if (isset($this->categoryCache[$category['ms3_parent']])) {
                $parent = $this->categoryCache[$category['ms3_parent']];
            }

            $this->getImportUtils()->getEventManager()->dispatch('import_category_before', ['category' => $category]);
            $cat = $this->getImportUtils()->getCategoryUtils()->createCategory($category, $parent);
            $this->getImportUtils()
                ->getCategoryUtils()
                ->copyCategoryImage($category, $this->getImportUtils()->getConfig()->getImportFileDir());
            $this->categoryCache[$category['ms3_id']] = $cat->getId();
            $this->getImportUtils()->getConsoleOutput()->advanceProgress();
        }
        $this->disableInactiveCategories();
        $this->getImportUtils()->getConsoleOutput()->finishProgress();
    }

    /**
     * Clean categories after import
     */
    public function clearMemory()
    {
        $this->categories = [];
        $this->categoryCache = [];
        $this->categoryReader->clearInstance();
        $this->getImportUtils()->reset();
    }

    /**
     * Mark categories as inactive before import starts
     */
    public function markCategoriesAsInactive()
    {
        $categories = $this->getImportUtils()->getCategoryUtils()->getImportedCategories();
        foreach ($categories as $category) {
            $category
                ->setStoreId($this->getStoreId())
                ->setData('ms3_active', 0)
                ->getResource()
                ->saveAttribute($category, 'ms3_active');
        }
    }

    /**
     * Disable inactive categories once the import finishes
     */
    public function disableInactiveCategories()
    {
        $categories = $this->getImportUtils()->getCategoryUtils()->getInactiveCategories();
        foreach ($categories as $category) {
            $category->setStoreId($this->getStoreId());
            $category->setData('is_active', 0)->getResource()->saveAttribute($category, 'is_active');
            $category->setData('include_in_menu', 0)->getResource()->saveAttribute($category, 'include_in_menu');
        }
    }

    public function setMarketAndLangId($marketId, $langId)
    {
        $this->categoryReader->setMarketAndLangId($marketId, $langId);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreId(): int
    {
        return $this->storeManager->getStore()->getId();
    }
}
