<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Import;

use Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing;
use Magento\Framework\App\ResourceConnection;
use Staempfli\CommerceImport\Api\Data\PriceImportInterface;
use Staempfli\CommerceImport\Model\Utils\Import as ImportUtils;
use Staempfli\CommerceImport\Model\Reader\Price as PriceReader;
use Staempfli\CommerceImport\Model\AbstractImport;
use Staempfli\CommerceImport\Model\Import\Processor\PriceImportProcessor;

class Price extends AbstractImport implements PriceImportInterface
{
    /**
     * @var array
     */
    protected $onlyProductSkus = [];
    /**
     * @var array
     */
    protected $productPricesToDelete = [];
    /**
     * @var array
     */
    protected $productPricesToImport = [];
    /**
     * @var PriceReader
     */
    private $priceReader;
    /**
     * @var PriceImportProcessor
     */
    private $importProcessor;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        PriceReader $priceReader,
        PriceImportProcessor $importProcessor,
        ResourceConnection $resourceConnection,
        ImportUtils $importUtils
    ) {
        parent::__construct($importUtils);
        $this->priceReader = $priceReader;
        $this->importProcessor = $importProcessor;
        $this->resourceConnection = $resourceConnection;
    }

    public function setOnlyProductSkus(string $onlyProductSkus)
    {
        if ($onlyProductSkus) {
            $this->onlyProductSkus = explode(',', $onlyProductSkus);
        } else {
            $this->onlyProductSkus = [];
        }
    }

    public function setMarketAndLangId($marketId, $langId)
    {
        $this->priceReader->setMarketAndLangId($marketId, $langId);
    }

    /**
     * Prepare and setData to be imported
     *
     * @return void
     */
    public function prepare()
    {
        if ($this->onlyProductSkus) {
            $this->priceReader->setProductSkusFilter($this->onlyProductSkus);
        }

        $this->deletePreviousPrices();
        $this->productPricesToImport = $this->priceReader->fetch();
        $this->importProcessor->setImportData($this->productPricesToImport);
    }

    protected function deletePreviousPrices()
    {
        $website = $this->priceReader->getStore()->getWebsite();
        $this->getImportUtils()
            ->getConsoleOutput()
            ->plain(sprintf('Deleting previous existing prices for Website "%s"', $website->getCode()));

        $productIds = array_keys($this->getImportUtils()->getProductUtils()->getImportedProducts());
        $tierPriceTable = $this->resourceConnection->getTableName(AdvancedPricing::TABLE_TIER_PRICE);
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            $tierPriceTable,
            [
                $connection->quoteInto('entity_id IN (?)', $productIds),
                $connection->quoteInto(
                    'website_id = ?',
                    $this->getImportUtils()->getTierPrice()->getWebsiteIdForTierPrice($website)
                ),
            ]
        );
    }

    /**
     * Valida data and set Data in Table that will be imported in next step
     *
     * @throws \Exception
     */
    public function validate()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Validate prices');
        if ($this->importProcessor->validateAndSetDataInTable()) {
            $this->getImportUtils()
                ->getConsoleOutput()
                ->info(sprintf('%d valid prices', count($this->importProcessor->getImportData())));
        } else {
            throw new \Exception($this->importProcessor->getLogTrace());
        }
    }

    /**
     * Import products
     */
    public function import()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Import prices');
        if (!$this->importProcessor->processImport()) {
            throw new \Exception($this->importProcessor->getLogTrace());
        }
        $this->getImportUtils()->getConsoleOutput()->info($this->importProcessor->getLogTrace());
    }

    /**
     * Clean up products
     */
    public function clearMemory()
    {
        $this->productPricesToDelete = [];
        $this->productPricesToImport = [];
        $this->onlyProductSkus = [];
        $this->importProcessor->reset();
        $this->priceReader->clearInstance();
        $this->getImportUtils()->reset();
    }
}
