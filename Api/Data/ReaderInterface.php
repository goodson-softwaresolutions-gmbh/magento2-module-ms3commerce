<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Api\Data;

/**
 * @api
 */
interface ReaderInterface
{
    /**
     * @return array
     */
    public function fetch();
}
