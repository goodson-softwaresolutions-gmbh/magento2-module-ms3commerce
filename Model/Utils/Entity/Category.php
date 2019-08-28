<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Utils\Entity;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Staempfli\CommerceImport\Model\Utils\Store as StoreUtils;

class Category
{
    const CATEGORY_KEY_ATTRIBUTE = 'ms3_guid';

    /**
     * @var array
     */
    private $storesCategoryRoot = [];
    /**
     * @var array
     */
    private $existingCategoryPaths = [];
    /**
     * @var array
     */
    private $categories = [];
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var CollectionFactory
     */
    private $categoryColFactory;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var \Magento\Framework\Filesystem\Directory\Read
     */
    private $sourceDirectory;
    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $mediaDirectory;
    /**
     * @var array
     */
    private $defaultCreationAttributes = [
        'display_mode' => \Magento\Catalog\Model\Category::DM_PRODUCT,
    ];
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StoreUtils
     */
    private $storeUtils;

    /**
     * Category constructor.
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $categoryColFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        CollectionFactory $categoryColFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        StoreUtils $storeUtils
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryColFactory = $categoryColFactory;
        $this->filesystem = $filesystem;
        $this->sourceDirectory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->storeManager = $storeManager;
        $this->storeUtils = $storeUtils;
    }

    public function reset()
    {
        $this->categories = [];
        $this->existingCategoryPaths = [];
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCollection()
    {
        return $this->categoryFactory->create()->getCollection()->setStoreId($this->getStoreId());
    }

    /**
     * @return array
     */
    public function loadCategories()
    {
        if (!$this->categories) {
            $categoryCollection = $this->getCollection()->addAttributeToSelect('*');
            foreach ($categoryCollection as $item) {
                $id = ($item->getData(self::CATEGORY_KEY_ATTRIBUTE))
                    ? $item->getData(self::CATEGORY_KEY_ATTRIBUTE)
                    : $item->getId();
                $this->categories[$id] = $item;
            }
        }
        return $this->categories;
    }

    /**
     * @param array $data
     * @param $parentId
     * @return \Magento\Catalog\Model\Category
     */
    public function createCategory(array $data, $parentId)
    {
        $this->loadCategories();

        /** @var \Magento\Catalog\Model\Category $category */
        if (isset($data[self::CATEGORY_KEY_ATTRIBUTE])
            && isset($this->categories[$data[self::CATEGORY_KEY_ATTRIBUTE]])) {
            $category = $this->categories[$data[self::CATEGORY_KEY_ATTRIBUTE]];
            $parentCategory = $this->categoryFactory->create()->load($parentId);
            $path = $parentCategory->getPath() . '/' . $category->getId();
            $category->setPath($path);
            $category->setParentId($parentCategory->getId());
        } else {
            $category = $this->categoryFactory->create();
            $parentCategory = $this->categoryFactory->create()->load($parentId);
            $category->setPath($parentCategory->getPath());
            $category->setParentId($parentCategory->getId());
            $category->addData($this->defaultCreationAttributes);
            $doubleSaveCategoryToSetDefaultDataInStoreScope = true;
        }

        $data['image'] = basename($data['image']);

        $categoryData = array_merge($category->getData(), $data);
        $category->setData($categoryData);
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setAttributeSetId($category->getDefaultAttributeSetId());
        $category->save();
        if (isset($doubleSaveCategoryToSetDefaultDataInStoreScope)) {
            $category->save();
        }
        return $category;
    }

    /**
     * @param $categoryId
     * @return mixed|null
     */
    public function getCategoryById($categoryId)
    {
        $this->loadCategories();
        return isset($this->categories[$categoryId]) ? $this->categories[$categoryId] : null;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return mixed
     */
    public function getImportCategoryByStore(\Magento\Store\Api\Data\StoreInterface $store)
    {
        if (!isset($this->storesCategoryRoot[$store->getId()])) {
            $this->storesCategoryRoot[$store->getId()] = $this->storeUtils->getImportRootCategory($store->getId());
        }

        return $this->storesCategoryRoot[$store->getId()];
    }

    /**
     * @param $category
     * @param string $sourceDirectory
     */
    public function copyCategoryImage($category, $sourceDirectory = '')
    {
        if (isset($category['image'])
            && !is_null($category['image'])//@codingStandardsIgnoreLine
        ) {
            $filePath = $this->sourceDirectory
                ->getRelativePath(
                    rtrim($sourceDirectory, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . ltrim($category['image'], DIRECTORY_SEPARATOR)
                );
            if ($this->sourceDirectory->isExist($filePath)) {
                $destinationDirectory = $this->mediaDirectory
                    ->getRelativePath('catalog' . DIRECTORY_SEPARATOR . 'category');
                if ($this->mediaDirectory->create($destinationDirectory)) {
                    $this->mediaDirectory->getDriver()->copy(
                        $this->sourceDirectory->getAbsolutePath($filePath),
                        $this->mediaDirectory->getAbsolutePath($destinationDirectory)
                                . DIRECTORY_SEPARATOR
                                . ltrim(basename($category['image']), DIRECTORY_SEPARATOR)
                    );
                }
            }
        }
    }

    /**
     * @param array $pathIds
     * @return string
     */
    public function getCategoryPathNamesByPathIds(array $pathIds = [])
    {
        $path = [];
        $collection = $this->getCollection()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('entity_id', ['in' => $pathIds])
            ->addFieldToFilter('level', ['gt' => 0])
            ->setOrder('level', 'ASC');

        foreach ($collection as $item) {
            $path[] = $item->getName();
        }
        return implode('/', $path);
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    public function getImportedCategories()
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('ms3_imported', 1);

        return $collection->getItems();
    }

    /**
     * @param int $storeId
     * @return \Magento\Framework\DataObject[]
     */
    public function getInactiveCategories()
    {
        $collection = $this->getCollection();
        $collection->addFieldToFilter('ms3_imported', 1);
        $collection->addFieldToFilter('ms3_active', 0);
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $collection->addFieldToFilter('path', ['like' => '%/' . $rootCategoryId . '/%']);

        return $collection->getItems();
    }

    /**
     * @param string $path
     * @return bool
     */
    public function categoryPathExists(string $path)
    {
        $existingCategoryPaths = $this->getExistingCategoryPaths();
        if (in_array($path, $existingCategoryPaths)) {
            return true;
        }
        return false;
    }

    protected function getExistingCategoryPaths()
    {
        if (!$this->existingCategoryPaths) {
            $collection = $this->getCollection()
                ->addAttributeToSelect('name');

            foreach ($collection as $category) {
                $structure = explode(CategoryProcessor::DELIMITER_CATEGORY, $category->getPath());
                $pathSize = count($structure); //@codingStandardsIgnoreLine
                $pathParts = [];
                for ($i = 1; $i < $pathSize; $i++) {
                    $pathParts[] = $collection->getItemById((int)$structure[$i])->getName();
                }
                $path = implode(CategoryProcessor::DELIMITER_CATEGORY, $pathParts);
                $this->existingCategoryPaths[] = $path;
            }
        }
        return $this->existingCategoryPaths;
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
