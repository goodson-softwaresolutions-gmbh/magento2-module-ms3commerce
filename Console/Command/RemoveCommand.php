<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Console\Command;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Utils\Attribute\AttributeFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\CategoryFactory;
use Staempfli\CommerceImport\Model\Utils\Entity\ProductFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class RemoveCommand
 * @package Staempfli\CommerceImport\Console\Command
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class RemoveCommand extends AbstractCommand
{
    const OPTION_ONLY = 'only';
    const OPTION_VALUE_PRODUCT = 'products';
    const OPTION_VALUE_ATTRIBUTE = 'attributes';
    const OPTION_VALUE_CATEGORY = 'categories';
    /**
     * @var \Staempfli\CommerceImport\Model\Utils\Attribute\Attribute
     */
    private $attributeUtils;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var AppState
     */
    private $appState;
    /**
     * @var AttributeFactory
     */
    private $attributeFactory;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * RemoveCommand constructor.
     * @param AttributeFactory $attributeFactory
     * @param ProductFactory $productFactory
     * @param CategoryFactory $categoryFactory
     * @param Registry $registry
     * @param AppState $appState
     * @param ConsoleOutput $consoleOutput
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        Registry $registry,
        AppState $appState,
        ConsoleOutput $consoleOutput
    ) {
        parent::__construct($consoleOutput);
        $this->attributeFactory = $attributeFactory;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->registry = $registry;
        $this->appState = $appState;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('ms3:remove')
            ->setDescription('Remove imported data')
            ->addOption(
                self::OPTION_ONLY,
                'o',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Select type to remove: --only=[%s|%s|%s]',
                    self::OPTION_VALUE_ATTRIBUTE,
                    self::OPTION_VALUE_PRODUCT,
                    self::OPTION_VALUE_CATEGORY
                )
            );
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
        $only = $input->getOption(self::OPTION_ONLY);
        if ($only) {
            switch ($only) {
                case self::OPTION_VALUE_ATTRIBUTE:
                    $this->removeAttributes();
                    break;
                case self::OPTION_VALUE_PRODUCT:
                    $this->removeProducts();
                    break;
                case self::OPTION_VALUE_CATEGORY:
                    $this->removeCategories();
                    break;
                default:
                    throw new \Exception(sprintf('Invalid type submitted: %s', $only));
            }
        } else {
            // Attributes need to be removed at the end,
            // because we cannot remove attributes that are used in Products or Categories unless they are deleted too
            $this->removeProducts();
            $this->removeCategories();
            $this->removeAttributes();
        }
    }

    protected function removeAttributes()
    {
        $this->getConsoleOutput()->title('Remove Attributes');
        $total = 0;
        $attributesToRemove = [];
        $attributes = [];
        $attributes[Product::ENTITY] = $this->getAttributeUtils()->getCustomAttributesByEntity(Product::ENTITY);
        $attributes[Category::ENTITY] = $this->getAttributeUtils()->getCustomAttributesByEntity(Category::ENTITY);

        foreach ($attributes as $entity => $items) {
            foreach ($items as $code => $item) {
                $attributesToRemove[$entity][$code] = $item;
                ++$total;
            }
        }

        if (!$total) {
            $this->getConsoleOutput()->comment('No attributes to remove');
            return false;
        }

        if (!$this->ask(sprintf('Are you sure you want to remove %d attributes? [y/N] ', $total))) {
            return false;
        }
        $this->getConsoleOutput()->startProgress($total);
        foreach ($attributesToRemove as $entity => $items) {
            foreach ($items as $code => $item) {
                $this->getAttributeUtils()->removeAttribute($code, $entity);
                $this->getConsoleOutput()->advanceProgress();
            }
        }
        $this->getConsoleOutput()->finishProgress();
        $this->getConsoleOutput()->info('Remove Attributes done');
        return true;
    }

    /**
     * @return int
     */
    protected function removeCategories()
    {
        $this->getConsoleOutput()->title('Remove Categories');
        $categoriesByLevel = [];
        $categories = $this->categoryFactory->create()->getImportedCategories();
        $total = count($categories);
        if (!$categories) {
            $this->getConsoleOutput()->comment('No categories to remove!');
            return false;
        }

        if (!$this->ask(sprintf('Are you sure you want to remove %d categories? [y/N] ', (int) $total))) {
            return false;
        }

        foreach ($categories as $category) {
            $categoriesByLevel[$category->getLevel()][$category->getId()] = $category;
        }

        $topLevel = array_shift($categoriesByLevel);
        $this->getConsoleOutput()->startProgress(count($topLevel));
        foreach ($topLevel as $item) {
            $item->delete(); //@codingStandardsIgnoreLine
            $this->getConsoleOutput()->advanceProgress();
        }
        $this->getConsoleOutput()->finishProgress();
        $this->getConsoleOutput()->info('Return Categories done');
        return true;
    }

    /**
     * @return int
     */
    protected function removeProducts()
    {
        $this->getConsoleOutput()->title('Remove Products');
        $products = $this->productFactory->create()->getImportedProducts();
        $total = count($products);
        if (!$products) {
            $this->getConsoleOutput()->comment('No products to remove!');
            return false;
        }

        if (!$this->ask(sprintf('Are you sure you want to remove %d products? [y/N] ', (int) $total))) {
            return false;
        }
        $this->getConsoleOutput()->startProgress($total);
        foreach ($products as $product) {
            $product->delete(); //@codingStandardsIgnoreLine
            $this->getConsoleOutput()->advanceProgress();
        }
        $this->getConsoleOutput()->finishProgress();
        $this->getConsoleOutput()->info('Remove products done');
        return true;
    }

    /**
     * @param $message
     * @return mixed
     */
    protected function ask($message)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($message, false);
        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * @return \Staempfli\CommerceImport\Model\Utils\Attribute\Attribute
     */
    private function getAttributeUtils()
    {
        if (!$this->attributeUtils) {
            $this->attributeUtils = $this->attributeFactory->create();
        }
        return $this->attributeUtils;
    }
}
