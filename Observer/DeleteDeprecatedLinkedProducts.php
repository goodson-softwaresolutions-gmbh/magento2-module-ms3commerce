<?php
/**
 * DeleteDeprecatedLinkedProducts
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Observer;

use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\ResourceModel\Product\LinkFactory;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProductImport;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\ImportExport\Model\Import as MagentoImport;
use Staempfli\CommerceImport\Model\Config;

class DeleteDeprecatedLinkedProducts implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var LinkFactory
     */
    private $linkFactory;
    /**
     * @var MagentoProductImport
     */
    private $productImport;

    protected $linkNameIdMapping = [
        '_related_' => Link::LINK_TYPE_RELATED,
        '_crosssell_' => Link::LINK_TYPE_CROSSSELL,
        '_upsell_' => Link::LINK_TYPE_UPSELL,
    ];

    public function __construct(
        Config $config,
        LinkFactory $linkFactory
    ) {
        $this->config = $config;
        $this->linkFactory = $linkFactory;
    }

    public function execute(Observer $observer)
    {
        /** @var MagentoProductImport $productImport */
        $this->productImport = $observer->getEvent()->getAdapter();
        if ($this->skipDeleteLinkedProducts()) {
            return;
        }

        while ($bunch = $this->productImport->getNextBunch()) {
            $deprecatedLinkIds = [];
            foreach ($bunch as $rowData) {
                $deprecatedLinkIds = array_merge($deprecatedLinkIds, $this->getProductDeprecatedLinkIds($rowData));
            }
            if ($deprecatedLinkIds) {
                $this->deleteLinks($deprecatedLinkIds);
            }
        }
        return $this;
    }

    private function skipDeleteLinkedProducts()
    {
        if (!$this->config->isStructureMaster() ||
            MagentoImport::BEHAVIOR_APPEND != $this->productImport->getBehavior()) {
            return true;
        }
        return false;
    }

    private function getProductDeprecatedLinkIds(array $rowData): array
    {
        $lastImportedLinks = $this->getLastImportedProductLinks($rowData);
        $allProductLinksSelect = $this->getAllProductLinksSelect($rowData['sku']);
        $deprecatedLinkIds = [];
        foreach ($this->productImport->getConnection()->fetchAll($allProductLinksSelect) as $linkData) {
            $linkKey = $this->getUniqueLinkKey($linkData['sku'], $linkData['linked_sku'], $linkData['link_type_id']);
            if (!in_array($linkKey, $lastImportedLinks)) {
                $deprecatedLinkIds[] = $linkData['link_id'];
            }
        }
        return $deprecatedLinkIds;
    }

    private function getAllProductLinksSelect(string $sku): Select
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Link $linkResource */
        $linkResource = $this->linkFactory->create();
        $select = $this->productImport->getConnection()->select()->from(
            ['pl' => $linkResource->getMainTable()],
            ['link_id', 'link_type_id']
        )
            ->joinLeft(
                ['pe' => 'catalog_product_entity'],
                'pe.entity_id = pl.product_id',
                ['sku' => 'sku']
            )
            ->joinLeft(
                ['pel' => 'catalog_product_entity'],
                'pel.entity_id = pl.linked_product_id',
                ['linked_sku' => 'sku']
            )
            ->where('pe.sku = ?', $sku)
            ->where('pl.link_type_id IN (?)', $this->linkNameIdMapping);

        return $select;
    }

    private function getLastImportedProductLinks(array $rowData): array
    {
        $productLinks = [];
        foreach ($this->linkNameIdMapping as $linkName => $linkTypeId) {
            if (isset($rowData[$linkName . 'sku'])) {
                $linkedSkus = explode($this->productImport->getMultipleValueSeparator(), $rowData[$linkName . 'sku']);
                foreach ($linkedSkus as $linkedSku) {
                    $linkedSku = trim($linkedSku);
                    $productLinks[] = $this->getUniqueLinkKey($rowData['sku'], $linkedSku, $linkTypeId);
                }
            }
        }
        return $productLinks;
    }

    private function getUniqueLinkKey(string $sku, string $linkedSku, int $linkTypeId)
    {
        return "{$sku}-{$linkedSku}-{$linkTypeId}";
    }

    private function deleteLinks(array $linkIds)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Link $linkResource */
        $linkResource = $this->linkFactory->create();
        $this->productImport->getConnection()->delete(
            $linkResource->getMainTable(),
            $this->productImport->getConnection()->quoteInto('link_id IN (?)', $linkIds)
        );
    }
}
