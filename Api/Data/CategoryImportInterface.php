<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Api\Data;

/**
 * @api
 */
interface CategoryImportInterface extends ImportInterface
{
    public function setMarketAndLangId($marketId, $langId);
}
