<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Test\Unit\Model\Reader\Entity;

use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class MapperTest extends \PHPUnit\Framework\TestCase
{
    private $model;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            '\Staempfli\CommerceImport\Model\Reader\Entity\Mapper',
            []
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // https://phpmd.org/rules/index.html
     */
    public function testMap()
    {
        $products = [
            [
            'sku' => 'PR1011',
            'name' => 'Leiter Topcase Alu',
            'description' => null,
            'short_description' => null,
            'image' => null,
            'weight' => null,
            'meta_title' => null,
            'meta_keywords' => null,
            'meta_description' => null,
            'new_from_date' => null,
            'new_to_date' => null,
            'country_of_manufacture' => null,
            'url_key' => null,
            'price' => '0.0',
            'qty' => 0,
            'visibility' => 'Catalog, Search',
            'categories' => 'Default Category',
            'is_in_stock' => 1,
            'id' => '3000005',
            'guid' => 'D843D0E4-8150-4E24-BE5D-C62DB7E469EB',
            'market_id' => '3',
            'lang_id' => '1',
            'type' => '2',
            'attribute_set_name' => 'OXID_Attribute_830E8A1A-0FB6-4FD0-98EF-5CABE950EAC5',
            'image_1' => null,
            'image_2' => null,
            'image_3' => null,
            'variations' =>
                [
                    0 =>
                        [
                            'id' => '0',
                            'type' => '2',
                            'parent_id' => '3000005',
                            'child_id' => '3000006',
                            'sort' => '1',
                        ],
                    1 =>
                        [
                            'id' => '1',
                            'type' => '2',
                            'parent_id' => '3000005',
                            'child_id' => '3000007',
                            'sort' => '2',
                        ],
                    2 =>
                        [
                            'id' => '2',
                            'type' => '2',
                            'parent_id' => '3000005',
                            'child_id' => '3000008',
                            'sort' => '3',
                        ],
                    3 =>
                        [
                            'id' => '3',
                            'type' => '2',
                            'parent_id' => '3000005',
                            'child_id' => '3000009',
                            'sort' => '4',
                        ],
                    4 =>
                        [
                            'id' => '4',
                            'type' => '2',
                            'parent_id' => '3000005',
                            'child_id' => '3000010',
                            'sort' => '5',
                        ],
                    5 =>
                        [
                            'id' => '5',
                            'type' => '2',
                            'parent_id' => '3000005',
                            'child_id' => '3000011',
                            'sort' => '6',
                        ],
                ],
            ],
            [
                'sku' => '5893118',
                'name' => 'Leiter Topcase Alu 5 St. Leifheit',
                'description' => null,
                'short_description' => null,
                'image' => null,
                'weight' => null,
                'meta_title' => null,
                'meta_keywords' => null,
                'meta_description' => null,
                'new_from_date' => null,
                'new_to_date' => null,
                'country_of_manufacture' => null,
                'url_key' => null,
                'price' => '0.0',
                'qty' => 0,
                'visibility' => 'Not Visible Individually',
                'categories' => 'Default Category',
                'is_in_stock' => 1,
                'id' => '3000007',
                'guid' => '197749D0-6D0B-4F7B-B0EF-58334CDA6CAE',
                'market_id' => '3',
                'lang_id' => '1',
                'type' => 3,
                'attribute_set_name' => 'OXID_Attribute_830E8A1A-0FB6-4FD0-98EF-5CABE950EAC5',
                'image_1' => null,
                'image_2' => null,
                'image_3' => null,
                'variations' =>
                    [
                        0 =>
                            [
                                'id' => '0',
                                'type' => '2',
                                'parent_id' => '3000005',
                                'child_id' => '3000007',
                                'sort' => '2',
                            ],
                    ],
            ]
        ];

        foreach ($products as $key => $product) {
            $data = $this->model->map($product, Product::TYPE_ID);

            $this->assertSame($products[$key]['id'], $data['ms3_id']);
            $this->assertSame($products[$key]['guid'], $data['ms3_guid']);
            $this->assertSame($products[$key]['market_id'], $data['ms3_market_id']);
            $this->assertSame($products[$key]['lang_id'], $data['ms3_lang_id']);
        }
    }
}
