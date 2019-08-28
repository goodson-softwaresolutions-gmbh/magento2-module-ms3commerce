<?php
/**
 * TypeConfigurable
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Reader\Product;

use Magento\CatalogImportExport\Model\Import\Product as MagentoProductImport;
use Magento\CatalogImportExport\Model\Export\Product as MagentoProductExport;
use Magento\Framework\App\ResourceConnection;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

class TypeConfigurable implements TypeInterface
{
    /**
     * @var Config
     */
    protected $configHelper;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var array
     */
    protected $variantAttributes = [];

    /**
     * TypeConfigurable constructor.
     * @param Config $configHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(Config $configHelper, ResourceConnection $resourceConnection)
    {
        $this->configHelper = $configHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function setProductChildrenConfiguration(array &$product, array $allProducts)
    {
        if (!isset($product['children']) || !is_array($product['children'])) {
            return;
        }

        $configurableVariationsData = [];
        foreach (array_keys($product['children']) as $childrenId) {
            if (isset($allProducts[$childrenId]) && isset($allProducts[$childrenId]['sku'])) {
                $variationAttributes = $this->getVariantsAttributesForProductId($childrenId);
                $childVariationData =
                    $this->getPreparedVariantDataFromChild($allProducts[$childrenId], $variationAttributes);
                if ($childVariationData) {
                    $configurableVariationsData[] = $childVariationData;
                }
            }
        }

        $product['configurable_variations'] =
            implode(MagentoProductImport::PSEUDO_MULTI_LINE_SEPARATOR, $configurableVariationsData);
    }

    /**
     * Get Variant attributes for specific Product Id
     *
     * @param $productId
     * @return array
     */
    protected function getVariantsAttributesForProductId($productId)
    {
        if (!isset($this->variantAttributes[$productId])) {
            $connection = $this->resourceConnection->getConnection(CommerceImportSetupConfig::DB_CONNECTION_SETUP);
            $select = $connection->select()
                ->from($connection->getTableName('m2m_attribute_variant'), 'attribute_code')
                ->where(sprintf('product_id = "%s"', $productId));
            $this->variantAttributes[$productId] = $connection->fetchCol($select);
        }
        return $this->variantAttributes[$productId];
    }

    /**
     * Get formatted configurable variants data from child
     *
     * @param array $childData
     * @param array $variationAttributes
     * @return bool|string
     */
    protected function getPreparedVariantDataFromChild(array $childData, array $variationAttributes)
    {
        if (!isset($childData['sku']) || !isset($childData[MagentoProductExport::COL_ADDITIONAL_ATTRIBUTES])) {
            return false;
        }
        $multipleValuesSeparator = $this->configHelper->getMultipleValuesSeparator();

        $attributesPairCodeValue =
            explode($multipleValuesSeparator, $childData[MagentoProductExport::COL_ADDITIONAL_ATTRIBUTES]);
        $variationData = ['sku' . MagentoProductImport::PAIR_NAME_VALUE_SEPARATOR . $childData['sku']];
        foreach ($attributesPairCodeValue as $pairCodeValue) {
            list($attributeCode, $value) = explode(MagentoProductImport::PAIR_NAME_VALUE_SEPARATOR, $pairCodeValue);
            if (in_array($attributeCode, $variationAttributes)) {
                $variationData[] = $attributeCode . MagentoProductImport::PAIR_NAME_VALUE_SEPARATOR .  $value;
            }
        }

        if (!$variationData) {
            return false;
        }

        return implode($multipleValuesSeparator, $variationData);
    }
}
