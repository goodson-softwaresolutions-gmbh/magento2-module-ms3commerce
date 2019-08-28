<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Api\Data;

/**
 * @api
 */
interface PriceReaderInterface extends ReaderInterface
{
    /**
     * @param array $productSkus
     */
    public function setProductSkusFilter(array $productSkus);
}
