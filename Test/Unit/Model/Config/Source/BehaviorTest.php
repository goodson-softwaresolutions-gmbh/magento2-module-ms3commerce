<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Import;

class BehaviorTest extends \PHPUnit\Framework\TestCase
{
    private $model;

    private $options;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            '\Staempfli\CommerceImport\Model\Config\Source\Behavior',
            []
        );

        $this->options = $this->model->toOptionArray();
    }

    public function testToOptionArrayValues()
    {
        $this->assertSame(Import::BEHAVIOR_APPEND, $this->options[0]['value']);
        $this->assertSame(Import::BEHAVIOR_REPLACE, $this->options[1]['value']);
        $this->assertSame(Import::BEHAVIOR_DELETE, $this->options[2]['value']);
    }

    public function testToOptionArraySize()
    {
        $this->assertSame(3, count($this->options), 'Expected 3 Options');
    }
}
