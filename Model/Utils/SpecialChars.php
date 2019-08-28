<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */

namespace Staempfli\CommerceImport\Model\Utils;

/**
 * Class SpecialChars
 * @package Staempfli\CommerceImport\Model
 */
class SpecialChars
{
    /**
     * @var array
     */
    private $removeChars = ['<sr>', '<SR>', '<0x000A>', '<CharStyle:bold>', '<b>', '</b>'];
    /**
     * @var array
     */
    private $replaceChars = ['', '', '<br />', '<strong>', '<strong>', '</strong>'];

    /**
     * @param array $entityData
     * @param array $fields
     */
    public function removeSpecialCharsFromEntityFields(array &$entityData, array $fields)
    {
        foreach ($fields as $field) {
            $string = $entityData[$field]??null;
            if ($string) {
                $newValue = str_replace($this->getRemoveChars(), '', $string);
                $entityData[$field] = $newValue;
            }
        }
    }

    /**
     * @param array $entityData
     * @param array $fields
     */
    public function replaceSpecialCharsFromEntityFields(array &$entityData, array $fields)
    {
        foreach ($fields as $field) {
            $string = $entityData[$field]??null;
            if ($string) {
                $newValue = str_replace($this->getRemoveChars(), $this->getReplaceChars(), $string);
                $entityData[$field] = $newValue;
            }
        }
    }

    public function getRemoveChars() : array
    {
        return $this->removeChars;
    }

    public function getReplaceChars() : array
    {
        return $this->replaceChars;
    }
}
