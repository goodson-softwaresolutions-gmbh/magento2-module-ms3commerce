<?php
/**
 * Copyright © 2019 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Console\Command;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\Registry;
use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Utils\Store as StoreUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\State as AppState;

class CategoryCopyDataCommand extends AbstractCommand
{
    /**
     * @var AppState
     */
    private $appState;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var CategoryResource
     */
    private $categoryResource;
    /**
     * @var StoreUtils
     */
    private $storeUtils;

    public function __construct(
        ConsoleOutput $consoleOutput,
        AppState $appState,
        Registry $registry,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryResource\Proxy $categoryResource,
        StoreUtils $storeUtils,
        $name = null
    ) {
        parent::__construct($consoleOutput, $name);
        $this->appState = $appState;
        $this->registry = $registry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryResource = $categoryResource;
        $this->storeUtils = $storeUtils;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('ms3:copy-category-data')
            ->setDescription('Copies data from all product family categories from one store view to another')
            ->addArgument('sourceStoreId', InputArgument::REQUIRED, 'Store ID of source categories')
            ->addArgument('targetStoreId', InputArgument::REQUIRED, 'Store ID of target categories');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            $this->getConsoleOutput()->info($e->getMessage());
        }
        $this->registry->register('isSecureArea', true);

        $sourceCategories = $this->getProductFamilyCategories((int) $input->getArgument('sourceStoreId'));
        $targetCategories = $this->getProductFamilyCategories((int) $input->getArgument('targetStoreId'));

        $numberCopiedCategories = 0;

        foreach ($sourceCategories as $categoryName => $sourceCategory) {
            if (!isset($targetCategories[$categoryName])) {
                continue;
            }

            $categoryData = $sourceCategory->debug();
            unset($categoryData['entity_id']);
            unset($categoryData['parent_id']);
            unset($categoryData['path']);
            unset($categoryData['position']);
            unset($categoryData['children_count']);
            unset($categoryData['updated_at']);
            unset($categoryData['created_at']);
            unset($categoryData['ms3_id']);
            unset($categoryData['ms3_guid']);
            unset($categoryData['ms3_market_id']);
            unset($categoryData['ms3_lang_id']);
            unset($categoryData['ms3_lang_id']);

            $targetCategory = $targetCategories[$categoryName];
            $targetCategory->addData($categoryData);
            $targetCategory->setStoreId(0);
            $this->categoryResource->save($targetCategory);

            $numberCopiedCategories++;
        }

        $output->writeln($numberCopiedCategories . ' categories have been copied successfully.');
    }

    /**
     * @param int $storeId
     * @return Category[] identified by Name
     */
    private function getProductFamilyCategories($storeId)
    {
        $categories = [];
        $importRootCategory = $this->storeUtils->getImportRootCategory($storeId);
        /** @var CategoryCollection $childrenCategories */
        $childrenCategories = $this->categoryCollectionFactory->create();
        $childrenCategories->setStoreId($storeId);
        $childrenCategories->addAttributeToFilter('parent_id', $importRootCategory->getId());
        $childrenCategories->addAttributeToSelect('*');
        foreach ($childrenCategories as $category) {
            $categories[$category->getName()] = $category;
        }
        return $categories;
    }
}
