<?php
/**
 * CategoryAssignationMode
 *
 * @copyright Copyright © 2017 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CategoryAssignationMode implements OptionSourceInterface
{
    const MODE_DEFAULT = 'default';
    const MODE_MS3_IDS = 'ms3_ids';

    /**
     * @var array $options
     */
    protected $options = [];

    /**
     * Get Options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                ['value' => self::MODE_DEFAULT, 'label' => __('Magento Default')],
                ['value' => self::MODE_MS3_IDS, 'label' => __('Using mS3 Ids')],
            ];
        }
        return $this->options;
    }
}
