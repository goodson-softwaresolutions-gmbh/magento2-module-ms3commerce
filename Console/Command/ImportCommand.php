<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Console\Command;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Console\Cli;
use Magento\Framework\Module\ResourceInterface;
use Magento\Store\Api\Data\StoreInterface;
use Staempfli\CommerceImport\Api\Data\AttributeImportInterface;
use Staempfli\CommerceImport\Api\Data\CategoryImportInterface;
use Staempfli\CommerceImport\Api\Data\PriceImportInterface;
use Staempfli\CommerceImport\Api\Data\ProductImportInterface;
use Staempfli\CommerceImport\Console\Output as ConsoleOutput;
use Staempfli\CommerceImport\Model\Config as ImportConfig;
use Staempfli\CommerceImport\Model\Import\Status;
use Staempfli\CommerceImport\Model\Reader;
use Staempfli\CommerceImport\Model\ReaderFactory;
use Staempfli\CommerceImport\Model\Store;
use Staempfli\CommerceImport\Setup\ConfigOptionsList;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Backup\Db\BackupFactory;
use Magento\Catalog\Model\Product\ImageFactory;
use Magento\Store\Model\App\EmulationFactory;

/**
 * Class ImportCommand
 * @package Staempfli\CommerceImport\Console\Command
 * @SuppressWarnings(PHPMD.TooManyFields) // https://phpmd.org/rules/index.html
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) // https://phpmd.org/rules/index.html
 */
class ImportCommand extends AbstractCommand
{
    /**
     * Command option constants
     */
    const OPTION_ONLY = 'only';
    const OPTION_BACKUP = 'backup';
    const OPTION_REINDEX = 'reindex';
    const OPTION_CLEAN_CACHE = 'cache';
    const OPTION_VALUE_PRODUCT = 'products';
    const OPTION_VALUE_ATTRIBUTE = 'attributes';
    const OPTION_VALUE_CATEGORY = 'categories';
    const OPTION_VALUE_PRICE = 'price';
    const OPTION_VALUE_VALIDATE = 'validate';
    const OPTION_VALUE_TRUE = 'true';
    const OPTION_VALUE_FALSE = 'false';
    const OPTION_IMPORT_PRODUCT_SKUS = 'only-skus';
    const OPTION_IMPORT_FORCE = 'force';
    const OPTION_NOTIFY = 'notify';
    const OPTION_IMPORT_STORES = 'only-stores';

    /**
     * @var bool
     */
    protected $hasAttributes = false;
    /**
     * @var bool
     */
    protected $hasProducts = false;
    /**
     * @var bool
     */
    protected $hasCategories = false;
    /**
     * @var bool
     */
    protected $hasPrices = false;
    /**
     * @var array
     */
    protected $mappingConfiguration = [];
    /**
     * @var array
     */
    protected $mappingConfigurations = [];
    /**
     * @var array
     */
    protected $importInformation = [];
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var StoreInterface
     */
    private $store;
    /**
     * @var StoreInterface[]
     */
    private $stores;
    /**
     * @var ReaderFactory
     */
    private $readerFactory;
    /**
     * @var AttributeImportInterface
     */
    private $attributeImport;
    /**
     * @var CategoryImportInterface
     */
    private $categoryImport;
    /**
     * @var ProductImportInterface
     */
    private $productImport;
    /**
     * @var PriceImportInterface
     */
    private $priceImport;
    /**
     * @var Status
     */
    private $status;
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;
    /**
     * @var ResourceInterface
     */
    private $resourceInterface;
    /**
     * @var BackupFactory
     */
    private $backupFactory;
    /**
     * @var AppState
     */
    private $appState;
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var EmulationFactory
     */
    private $emulationFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ImportCommand constructor.
     * @param ReaderFactory $readerFactory
     * @param AttributeImportInterface $attributeImport
     * @param CategoryImportInterface $categoryImport
     * @param ProductImportInterface $productImport
     * @param PriceImportInterface $priceImport
     * @param Status $status
     * @param DeploymentConfig $deploymentConfig
     * @param ResourceInterface $resourceInterface
     * @param BackupFactory $backupFactory
     * @param AppState $appState
     * @param ImageFactory $imageFactory
     * @param EmulationFactory $emulationFactory
     * @param ConsoleOutput $consoleOutput
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ReaderFactory $readerFactory,
        AttributeImportInterface $attributeImport,
        CategoryImportInterface $categoryImport,
        ProductImportInterface $productImport,
        PriceImportInterface $priceImport,
        Status $status,
        DeploymentConfig $deploymentConfig,
        ResourceInterface $resourceInterface,
        BackupFactory $backupFactory,
        AppState $appState,
        ImageFactory $imageFactory,
        EmulationFactory $emulationFactory,
        ConsoleOutput $consoleOutput,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($consoleOutput);
        $this->readerFactory = $readerFactory;
        $this->attributeImport = $attributeImport;
        $this->categoryImport = $categoryImport;
        $this->productImport = $productImport;
        $this->priceImport = $priceImport;
        $this->status = $status;
        $this->deploymentConfig = $deploymentConfig;
        $this->resourceInterface = $resourceInterface;
        $this->backupFactory = $backupFactory;
        $this->appState = $appState;
        $this->imageFactory = $imageFactory;
        $this->emulationFactory = $emulationFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('ms3:import')
            ->setDescription('Import Data from mS3Commerce')
            ->addOption(
                self::OPTION_ONLY,
                'o',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Select type for import: --only=[%s|%s|%s|%s|%s]',
                    self::OPTION_VALUE_ATTRIBUTE,
                    self::OPTION_VALUE_PRODUCT,
                    self::OPTION_VALUE_CATEGORY,
                    self::OPTION_VALUE_PRICE,
                    self::OPTION_VALUE_VALIDATE
                )
            )->addOption(
                self::OPTION_BACKUP,
                'b',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Create Database backup before import: --backup=[%s|%s]',
                    self::OPTION_VALUE_TRUE,
                    self::OPTION_VALUE_FALSE
                ),
                self::OPTION_VALUE_TRUE
            )->addOption(
                self::OPTION_REINDEX,
                'r',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Run reindex after import: --reindex=[%s|%s]',
                    self::OPTION_VALUE_TRUE,
                    self::OPTION_VALUE_FALSE
                ),
                self::OPTION_VALUE_TRUE
            )->addOption(
                self::OPTION_CLEAN_CACHE,
                'c',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Clean cache after import: --cache=[%s|%s]',
                    self::OPTION_VALUE_TRUE,
                    self::OPTION_VALUE_FALSE
                ),
                self::OPTION_VALUE_TRUE
            )->addOption(
                self::OPTION_IMPORT_PRODUCT_SKUS,
                'os',
                InputOption::VALUE_REQUIRED,
                'Import only specific skus (coma separated values)',
                ''
            )->addOption(
                self::OPTION_IMPORT_FORCE,
                'f',
                InputOption::VALUE_NONE,
                'force import even when status is not valid'
            )
            ->addOption(
                self::OPTION_NOTIFY,
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'Notify: --notify=[%s|%s]',
                    self::OPTION_VALUE_TRUE,
                    self::OPTION_VALUE_FALSE
                ),
                self::OPTION_VALUE_TRUE
            )
            ->addOption(
                self::OPTION_IMPORT_STORES,
                'ss',
                InputOption::VALUE_REQUIRED,
                'Import only specific stores (coma separated values)',
                ''
            )
        ;
    }

    /**
     * Check whether the ms3 import status is ok to start import
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if (!$input->getOption(self::OPTION_IMPORT_FORCE) && !$this->status->isStatusValidToStart()) {
            ;
            $this->getConsoleOutput()->warning(
                sprintf(
                    'Import Status is not valid to start import. Status must be "%s"',
                    Status::IMPORT_STATUS_READY
                )
            );
            exit(Cli::RETURN_FAILURE); //@codingStandardsIgnoreLine
        }
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExitExpression) // https://phpmd.org/rules/index.html
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->status->updateStatusStart();
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            $this->getConsoleOutput()->info($e->getMessage());
        }
        $version = $this->getModuleVersion();
        $this->getConsoleOutput()->banner(
            sprintf("mediaSolution3 Commerce Import [ver: %s]", $version),
            'green',
            'default'
        );

        $time = microtime(true);

        try {
            $this->status->updateStatusStart();
            $this->prepareImportBeforeRunning();
            $this->runImports();
            $this->reindex();
            $this->cleanCache();
        } catch (\Throwable $e) {
            $this->status->updateStatusFailed();
            $errorMessage = sprintf(
                "%s\n%s\nError Trace: %s",
                $this->stringifyConfigurationMapping($this->mappingConfiguration),
                $e->getMessage(),
                $e->getTraceAsString()
            );
            $this->notify($errorMessage);
            throw new \Exception($errorMessage);
        }
        $this->status->updateStatusFinish();
        $this->getConsoleOutput()->title('Memory Usage');
        $totalTime = round(microtime(true) - $time, 2);
        $this->getConsoleOutput()->banner('Elapsed time: ' . $totalTime . 's', 'green');
        $successMessage = sprintf(
            "Import finished in %s s\n%s",
            $totalTime,
            $this->stringifyConfigurationMapping($this->mappingConfiguration)
        );
        $this->notify($successMessage);
        exit(Cli::RETURN_SUCCESS); //@codingStandardsIgnoreLine
    }

    private function prepareImportBeforeRunning()
    {
        /** Validate Import Config **/
        $connections = $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS);
        if (!isset($connections['import'])) {
            throw new \Exception('Import Connection not set! Please execute command: ms3:config:database');
        }
        $this->validateSourceDatabase($connections);
        $this->createDefaultAttributeSetIfNotExists();
        $this->getImportInformation();
        $this->validateMappingConfiguration();
        $this->makeBackup();
        $this->prepareDatabase();
    }

    /**
     * Run imports
     */
    private function runImports()
    {
        $only = $this->input->getOption(self::OPTION_ONLY);
        if ($only) {
            switch ($only) {
                case self::OPTION_VALUE_ATTRIBUTE:
                    $this->importAttributes();
                    break;
                case self::OPTION_VALUE_CATEGORY:
                    $this->importCategories();
                    break;
                case self::OPTION_VALUE_PRODUCT:
                    $this->importProducts();
                    break;
                case self::OPTION_VALUE_PRICE:
                    $this->importPrice();
                    break;
                default:
                    throw new \Exception(sprintf('Invalid type submitted: %s', $only));
            }
        } else {
            $this->importAttributes();
            $this->importCategories();
            $this->importProducts();
            $this->importPrice();
        }
    }

    /**
     * @return void|\Exception
     */
    private function getImportInformation()
    {
        $this->getConsoleOutput()->title('Import Information');
        $this->importInformation = $this->getReader()->getImportInformation();
        $this->hasAttributes = !empty(array_filter($this->importInformation, function ($row) {
            return $row['total_attributes'] > 0;
        }));
        $this->hasCategories = !empty(array_filter($this->importInformation, function ($row) {
            return $row['total_categories'] > 0;
        }));
        $this->hasProducts = !empty(array_filter($this->importInformation, function ($row) {
            return $row['total_products'] > 0;
        }));
        $this->hasPrices = !empty(array_filter($this->importInformation, function ($row) {
            return $row['total_prices'] > 0;
        }));

        $tableHeadings = [
            'Market ID',
            'Lang ID',
            'Products',
            'Categories',
            'Attributes',
            'Prices',
        ];

        $this->getConsoleOutput()
            ->table($tableHeadings, $this->importInformation);
    }

    /**
     * @return void
     */
    private function importAttributes()
    {
        if (!$this->hasAttributes) {
            $this->getConsoleOutput()->title('No Attributes to import', 'title');
            return;
        }
        $this->getConsoleOutput()->title('Attributes process start');
        foreach ($this->getStores() as $store) {
            $this->setStore($store);
            $this->startEmulation();
            $this->attributeImport->prepare();
            $this->attributeImport->validate();
            $this->attributeImport->import();
            $this->attributeImport->clearMemory();
            $this->stopEmulation();
        }
    }

    /**
     * @return void
     */
    private function importCategories()
    {
        if (!$this->hasCategories) {
            $this->getConsoleOutput()->title('No Categories to import', 'title');
            return;
        }
        $this->getConsoleOutput()->title('Categories process start');
        foreach ($this->getStores() as $store) {
            $this->setStore($store);
            $this->startEmulation();
            $this->categoryImport->prepare();
            $this->categoryImport->validate();
            $this->categoryImport->import();
            $this->categoryImport->clearMemory();
            $this->stopEmulation();
        }
    }

    /**
     * @return void
     */
    private function importProducts()
    {
        if (!$this->hasProducts) {
            $this->getConsoleOutput()->title('No Products to import');
            return;
        }
        $this->getConsoleOutput()->title('Product process start');
        foreach ($this->getStores() as $store) {
            $this->setStore($store);
            $this->setMarketAndLangParameters();
            $this->productImport->setOnlyProductSkus($this->input->getOption(self::OPTION_IMPORT_PRODUCT_SKUS));
            $this->productImport->prepare();
        }
        $this->productImport->validate();
        $this->productImport->setStores($this->getStores());
        $this->productImport->import();
        $this->productImport->clearMemory();
    }

    /**
     * @return void
     */
    private function importPrice()
    {
        if (!$this->hasPrices) {
            $this->getConsoleOutput()->title('No Prices to import');
            return;
        }
        if (!$this->getReader()->isStructureMaster()) {
            $this->getConsoleOutput()->comment('Price import skipped. Import only possible on Master Structure');
            return;
        }
        $this->getConsoleOutput()->title('Price process start');
        foreach ($this->getStores() as $store) {
            $this->setStore($store);
            $this->setMarketAndLangParameters();
            $this->startEmulation();

            $this->priceImport->setOnlyProductSkus($this->input->getOption(self::OPTION_IMPORT_PRODUCT_SKUS));
            $this->priceImport->prepare();
            $this->priceImport->validate();
            $this->priceImport->import();
            $this->priceImport->clearMemory();
            $this->stopEmulation();
        }
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    private function getModuleVersion()
    {
        $dbVersion = $this->resourceInterface->getDbVersion('Staempfli_CommerceImport');
        $dataVersion = $this->resourceInterface->getDataVersion('Staempfli_CommerceImport');

        if (!$dataVersion || ($dbVersion !== $dataVersion)) {
            throw new \Exception('Please upgrade your database: Run "bin/magento setup:upgrade"');
        }
        return $dataVersion;
    }

    /**
     * Validate mapping
     */
    private function validateMappingConfiguration()
    {
        $this->getConsoleOutput()->title('Validate Mapping Configuration');
        $this->getReader()->validateConfig();
        foreach ($this->getStores() as $store) {
            $this->startEmulation();
            $rootCategory = $this->getReader()->getReaderUtils()->getCategoryUtils()->getImportCategoryByStore($store);
            $master = ($this->getReader()->getReaderUtils()->getConfig()->isStructureMaster($store)) ? 'yes' : 'no';

            $this->mappingConfigurations[$store->getId()] = [
                'Website'    => $store->getWebsite()->getName(),
                'Store'      => $store->getGroup()->getName(),
                'Store View' => $store->getName(),
                'Category'   => $rootCategory->getName(),
                'Master'     => $master
            ];
            $this->stopEmulation();
        }

        $this->getConsoleOutput()
            ->table(array_keys(current($this->mappingConfigurations)), $this->mappingConfigurations);
    }

    /**
     * @param $connections
     */
    private function validateSourceDatabase($connections)
    {
        $this->getConsoleOutput()->plain(
            sprintf(
                'Validate source database [%s] ',
                $connections[ConfigOptionsList::DB_CONNECTION_NAME][ConfigOptionsListConstants::KEY_NAME]
            )
        );
        $this->getReader()->validateDatabase();
    }

    /**
     * Create Default attribute set
     */
    private function createDefaultAttributeSetIfNotExists()
    {
        $name = 'mS3_Default_Set';
        try {
            $this->getReader()->getReaderUtils()->getAttributeSetUtils()->getAttributeSetId(Product::ENTITY, $name);
            $this->getConsoleOutput()->plain(sprintf('Default Attribute Set [%s] exists', $name));
        } catch (\Exception $e) {
            $this->getReader()->getReaderUtils()->getAttributeSetUtils()->addAttributeSet(Product::ENTITY, $name);
            $this->getConsoleOutput()->plain(sprintf('Default Attribute Set [%s] created', $name));
        }
    }

    /**
     * Create database backup
     */
    private function makeBackup()
    {
        if ($this->input->getOption(self::OPTION_BACKUP) === self::OPTION_VALUE_TRUE
            && $this->input->getOption(self::OPTION_ONLY) !== self::OPTION_VALUE_VALIDATE
        ) {
            $this->getConsoleOutput()->title('Create Database Backup', 'title');
            $info = $this->getReader()->getMarketAndLangId();
            /** @var $backup \Magento\Backup\Model\Backup * */
            $backup = $this->backupFactory->createBackupModel()
                ->setTime(time())
                ->setType('db')
                ->setName(
                    sprintf(
                        'import_%s_%s',
                        $info['market_id'],
                        $info['lang_id']
                    )
                )
                ->setPath('ms3commerce');

            /** @var $backupDb \Magento\Backup\Model\Db * */
            $backupDb = $this->backupFactory->createBackupDbModel();
            $backupDb->createBackup($backup);
            $this->getConsoleOutput()->info($backup->getFileName());
        }
    }

    private function prepareDatabase()
    {
        $this->getReader()->prepareDatabase();
    }

    /**
     * Reindex
     */
    public function reindex()
    {
        if ($this->input->getOption(self::OPTION_REINDEX) === self::OPTION_VALUE_TRUE
            && $this->input->getOption(self::OPTION_ONLY) !== self::OPTION_VALUE_VALIDATE
        ) {
            $this->getConsoleOutput()->title('Reindex all');
            $command = $this->getApplication()->find('indexer:reindex');
            $arguments = [
                'command' => 'indexer:reindex',
            ];
            $input = new ArrayInput($arguments);
            $command->run($input, $this->output);
        }
    }

    /**
     * Clean Cache
     */
    public function cleanCache()
    {
        if ($this->input->getOption(self::OPTION_CLEAN_CACHE) === self::OPTION_VALUE_TRUE
            && $this->input->getOption(self::OPTION_ONLY) !== self::OPTION_VALUE_VALIDATE
        ) {
            $this->getConsoleOutput()->title('Clean cache');
            $command = $this->getApplication()->find('cache:clean');
            $arguments = [
                'command' => 'cache:clean',
            ];
            $input = new ArrayInput($arguments);
            $command->run($input, $this->output);
            // Flush Product Images Cache
            $this->getConsoleOutput()->plain('product_image');
            $this->imageFactory->create()->clearCache();
        }
    }

    /**
     * @return \Staempfli\CommerceImport\Model\Reader
     */
    public function getReader()
    {
        if (!$this->reader) {
            $this->reader = $this->readerFactory->create();
        }
        return $this->reader;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        if (!$this->store) {
            $this->store = $this->getReader()->getStore();
        }
        return $this->store;
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores()
    {
        if (!$this->stores) {
            $this->stores = $this->getReader()->getStores();

            $onlyStores = $this->input->getOption(self::OPTION_IMPORT_STORES);
            if (!empty($onlyStores)) {
                $onlyStores = explode(',',$onlyStores);
                foreach ($this->stores as $k => $store) {
                    if (!in_array($store->getId(), $onlyStores)) {
                        unset($this->stores[$k]);
                    }
                }
            }
        }
        return $this->stores;
    }

    private function startEmulation()
    {
        $storeId = $this->getStore()->getId();
        $this->setMarketAndLangParameters();
        $this->getEmulation()
            ->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_ADMINHTML);
    }

    private function stopEmulation()
    {
        $this->getEmulation()->stopEnvironmentEmulation();
    }

    private function getEmulation()
    {
        if (!$this->emulation) {
            $this->emulation = $this->emulationFactory->create();
        }
        return $this->emulation;
    }

    /**
     * @param string $message
     */
    private function notify(string $message)
    {
        if ($this->input->getOption(self::OPTION_NOTIFY) === self::OPTION_VALUE_TRUE) {
            $notification = sprintf(
                "<strong>Notification Date:</strong> %s\n\n" .
                "<strong>Command:</strong> %s\n\n" .
                "<strong>Message:</strong>\n%s",
                date('c'),
                str_replace("'", "", $this->input->__toString()),
                $message
            );
            $this->getReader()
                ->getReaderUtils()
                ->getEventManager()
                ->notify($notification);
        }
    }

    private function stringifyConfigurationMapping(array $configurationMapping): string
    {
        $result = '';
        foreach ($configurationMapping as $label => $value) {
            $result .= sprintf("%s: %s\n", $label, $value);
        }
        return $result;
    }

    private function setMarketAndLangParameters()
    {
        $storeId = $this->getStore()->getId();
        $marketId = $this->scopeConfig->getValue(ImportConfig::XML_PATH_MAPPING_MARKET_ID, 'store', $storeId);
        $langId = $this->scopeConfig->getValue(ImportConfig::XML_PATH_MAPPING_LANG_ID, 'store', $storeId);
        $this->attributeImport->setMarketAndLangId(
            $marketId,
            $langId
        );
        $this->categoryImport->setMarketAndLangId(
            $marketId,
            $langId
        );
        $this->productImport->setMarketAndLangId(
            $marketId,
            $langId
        );
        $this->priceImport->setMarketAndLangId(
            $marketId,
            $langId
        );
    }
}
