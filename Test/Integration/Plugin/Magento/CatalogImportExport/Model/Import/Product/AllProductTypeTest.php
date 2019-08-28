<?php
/**
 * AllProductType
 *
 * @copyright Copyright (c) 2017 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Test\Integration\Plugin\Magento\CatalogImportExport\Model\Import\Product;

class AllProductTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product
     */
    protected $productImport;

    protected $productTypes = [
        'simple',
        'bundle',
        'configurable',
        'downloadable',
        'grouped'
    ];

    protected function setUp()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->productImport = $objectManager->create('Magento\CatalogImportExport\Model\Import\Product');
    }

    /**
     * @dataProvider clearEmptyDataDataProvider
     */
    public function testAvoidClearEmptyData($rowData, $expectedAttributes)
    {
        foreach ($this->productTypes as $type) {
            $productTypeModel = $this->productImport->retrieveProductTypeByName($type);
            $actualAttributes = $productTypeModel->clearEmptyData($rowData);
            foreach ($expectedAttributes as $key => $value) {
                $this->assertArrayHasKey($key, $actualAttributes, sprintf('Attribute key[%s] shouldn\'t not have been cleared', $key));
                $this->assertEquals($value, $actualAttributes[$key], sprintf('Attribute key[%s] must contain a value = %s', $key, $value));
            }
        }
    }

    public function clearEmptyDataDataProvider()
    {
        return [
            [
                [
                    'sku' => '',
                    'store_view_code' => 'German',
                    '_attribute_set' => 'Default',
                    'product_type' => '',
                    'name' => '',
                    'description' => null,
                    'short_description' => false,
                    'price' => 0,
                ],
                [
                    'sku' => '',
                    'store_view_code' => 'German',
                    '_attribute_set' => 'Default',
                    'product_type' => '',
                    'name' => '',
                    'description' => null,
                    'short_description' => false,
                    'price' => 0,
                ],
            ],
        ];
    }
}
