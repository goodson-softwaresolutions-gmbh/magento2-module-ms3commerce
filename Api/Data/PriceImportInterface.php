<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Api\Data;

/**
 * @api
 */
interface PriceImportInterface extends ImportInterface
{
    public function setOnlyProductSkus(string $onlyProductSkus);

    public function setMarketAndLangId($marketId, $langId);
}
