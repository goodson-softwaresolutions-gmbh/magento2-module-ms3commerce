<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Import\Processor;

use Magento\ImportExport\Model\Import as MagentoImport;
use Magento\ImportExport\Model\ImportFactory;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Adapter\ArrayAdapterFactory;
use Staempfli\CommerceImport\Model\Import\Product\CategoryAssignation;

abstract class AbstractImportProcessor
{
    /**
     * @var array
     */
    protected $importData = [];
    /**
     * @var string
     */
    protected $logTrace = '';
    /**
     * @var boolean
     */
    protected $validationResult = null;
    /**
     * @var MagentoImport
     */
    protected $importModel;
    /**
     * @var ImportFactory
     */
    private $importFactory;
    /**
     * @var ArrayAdapterFactory
     */
    private $arrayAdapterFactory;
    /**
     * @var CategoryAssignation
     */
    protected $categoryAssignation;
    /**
     * @var Config
     */
    protected $config;

    /**
     * AbstractImportProcessor constructor.
     * @param ImportFactory $importFactory
     * @param ArrayAdapterFactory $arrayAdapterFactory
     * @param CategoryAssignation $categoryAssignation
     * @param Config $config
     */
    public function __construct(
        ImportFactory $importFactory,
        ArrayAdapterFactory $arrayAdapterFactory,
        CategoryAssignation $categoryAssignation,
        Config $config
    ) {
        $this->importFactory = $importFactory;
        $this->arrayAdapterFactory = $arrayAdapterFactory;
        $this->categoryAssignation = $categoryAssignation;
        $this->config = $config;
    }

    /**
     * @return MagentoImport
     */
    protected function getImportModel()
    {
        if (!$this->importModel) {
            $this->importModel = $this->importFactory->create();
            $this->importModel->setData($this->getSettings());
        }
        return $this->importModel;
    }

    abstract protected function getSettings();

    public function processImport()
    {
        if ($this->validateAndSetDataInTable()) {
            $resultImport = $this->getImportModel()->importSource();
            $this->handleImportResult();
            return $resultImport;
        }

        throw new \Exception($this->getLogTrace());
    }

    /**
     * @return array
     */
    public function getImportData()
    {
        return $this->importData;
    }

    public function setImportData(array $importData)
    {
        $this->importData = $importData;
    }

    public function addImportData(array $importData)
    {
        $this->importData = array_merge($this->importData, $importData);
    }

    /**
     * Check that import data is valid and set Data in table for the import
     *
     * @return bool
     */
    public function validateAndSetDataInTable()
    {
        if ($this->validationResult === null) {
            $importData = $this->getImportData();
            $source = $this->arrayAdapterFactory->create(['data' => $importData]);
            /**
             * IMPORTANT: validateSource method also set the Source in importexport_importdata table
             * The content of this table will be data used for the import in the next step
             */
            $this->validationResult = $this->getImportModel()->validateSource($source);
            $this->addToLogTrace();
        }
        return $this->validationResult;
    }

    /**
     * Handle result from import
     */
    protected function handleImportResult()
    {
        $this->addToLogTrace();
        if (!$this->getImportModel()->getErrorAggregator()->hasToBeTerminated()) {
            $this->getImportModel()->invalidateIndex();
        }
    }

    /**
     * Add logs to trace
     */
    public function addToLogTrace()
    {
        $this->logTrace .= $this->getImportModel()->getFormatedLogTrace();
        $ms3ErrorMessages = $this->getMS3Errors();//@codingStandardsIgnoreLine
        if ($ms3ErrorMessages) {
            $this->logTrace .= PHP_EOL . '---- mS3 Errors Info: ----' . PHP_EOL;
            $this->logTrace .= implode(PHP_EOL, $ms3ErrorMessages);//@codingStandardsIgnoreLine
        }
    }

    /**
     * Get error with relevant info ms3 data import
     *
     * @return array
     */
    protected function getMS3Errors()
    {
        $errorMessages = [];
        $errorAggregator = $this->getImportModel()->getErrorAggregator();

        if ($errorAggregator->getErrorsCount()) {
            $productSkuWithErrors = [];
            $importData = $this->getImportData();
            foreach ($errorAggregator->getAllErrors() as $error) {
                $productSku = isset($importData[$error->getRowNumber()])
                    ? $importData[$error->getRowNumber()]['sku']
                    : 'Undefined';
                $productSkuWithErrors[] = $productSku;
                $errorMessages[] = $productSku . ': ' . $error->getErrorMessage();
                if ($error->getErrorDescription()) {
                    $errorMessages[] = '-- Description: ' . $error->getErrorDescription();
                }
            }
            // Add summary of all products with errors at the beginning of messages
            $productsSummaryMessage = 'Product Sku(s): ' . implode(', ', array_unique($productSkuWithErrors));
            array_unshift($errorMessages, $productsSummaryMessage);
        }
        return $errorMessages;
    }

    /**
     * @return string
     */
    public function getLogTrace()
    {
        return $this->logTrace;
    }

    public function reset() {
        $this->importData = [];
        $this->logTrace = '';
        $this->validationResult = null;
        $this->importModel = null;
    }
}
