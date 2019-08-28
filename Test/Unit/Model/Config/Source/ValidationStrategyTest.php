<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class ValidationStrategyTest extends \PHPUnit\Framework\TestCase
{
    private $model;

    private $options;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            '\Staempfli\CommerceImport\Model\Config\Source\ValidationStrategy',
            []
        );

        $this->options = $this->model->toOptionArray();
    }

    public function testToOptionArrayValues()
    {
        $this->assertSame(ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR, $this->options[0]['value']);
        $this->assertSame(ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS, $this->options[1]['value']);
    }

    public function testToOptionArraySize()
    {
        $this->assertSame(2, count($this->options), 'Expected 2 Options');
    }
}
