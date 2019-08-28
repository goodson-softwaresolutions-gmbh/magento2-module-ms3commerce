<?php
/**
 * TypeInterface
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Reader\Product;

interface TypeInterface
{
    /**
     * Set Product children specific configuration for import
     *
     * @param array $product
     * @param array $allProducts
     * @return mixed
     */
    public function setProductChildrenConfiguration(array &$product, array $allProducts);
}
