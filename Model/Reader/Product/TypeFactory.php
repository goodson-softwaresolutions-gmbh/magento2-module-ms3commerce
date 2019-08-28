<?php
/**
 * TypeFactory
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Reader\Product;

use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Staempfli\CommerceImport\Model\Reader\Product\TypeInterface as ProductTypeInterface;

class TypeFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceTypeNames = [
        Grouped::TYPE_CODE => '\\Staempfli\\CommerceImport\\Model\\Reader\\Product\\TypeGrouped',
        Configurable::TYPE_CODE => '\\Staempfli\\CommerceImport\\Model\\Reader\\Product\\TypeConfigurable',
    ];

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create corresponding product class instance
     *
     * @param $productType
     * @param array $data
     * @return ProductTypeInterface
     */
    public function create($productType, array $data = [])
    {
        if (empty($this->instanceTypeNames[$productType])) {
            throw new InvalidArgumentException('"' . $productType . ': isn\'t allowed');
        }

        $resultInstance = $this->objectManager->create($this->instanceTypeNames[$productType], $data);
        if (!$resultInstance instanceof ProductTypeInterface) {
            throw new InvalidArgumentException(get_class($resultInstance) . ' isn\'t instance of ProductTypeInterface');
        }
        
        return $resultInstance;
    }
}
