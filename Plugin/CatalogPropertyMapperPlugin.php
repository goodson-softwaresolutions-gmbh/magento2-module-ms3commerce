<?php
/**
 * CatalogFixPropertyMapper
 *
 * @copyright Copyright © 2017 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Plugin;

use Magento\Catalog\Model\ResourceModel\Setup\PropertyMapper;

class CatalogPropertyMapperPlugin
{
    private $databaseFieldToMagentoKeyMapping = [
        'is_visible' => 'visible',
        'is_searchable' => 'searchable',
        'is_filterable' => 'filterable',
        'is_comparable' => 'comparable',
        'is_visible_on_front' => 'visible_on_front',
        'is_wysiwyg_enabled' => 'wysiwyg_enabled',
        'is_visible_in_advanced_search' => 'visible_in_advanced_search',
        'is_filterable_in_search' => 'filterable_in_search',
        'is_used_for_promo_rules' => 'used_for_promo_rules',
    ];

    /**
     * @param PropertyMapper $subject
     * @param array $input
     * @param $entityTypeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeMap(PropertyMapper $subject, array $input, $entityTypeId)
    {
        foreach ($this->databaseFieldToMagentoKeyMapping as $databaseField => $magentoKey) {
            if (isset($input[$databaseField]) && !isset($input[$magentoKey])) {
                $input[$magentoKey] = $input[$databaseField];
            }
        }

        return [$input, $entityTypeId];
    }
}
