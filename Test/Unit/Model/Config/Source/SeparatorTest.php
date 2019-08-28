<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Import;

class SeparatorTest extends \PHPUnit\Framework\TestCase
{
    private $model;

    private $options;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            '\Staempfli\CommerceImport\Model\Config\Source\Separator',
            []
        );

        $this->options = $this->model->toOptionArray();
    }

    public function testToOptionArrayValues()
    {
        $this->assertSame(',', $this->options[0]['value']);
        $this->assertSame(';', $this->options[1]['value']);
        $this->assertSame('§', $this->options[2]['value']);
        $this->assertSame('#', $this->options[3]['value']);
    }

    public function testToOptionArraySize()
    {
        $this->assertSame(4, count($this->options), 'Expected 4 Options');
    }
}
