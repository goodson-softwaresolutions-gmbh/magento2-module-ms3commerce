<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Helper\Catalog\Helper\Product\Edit\Action;

class Attribute extends \Magento\Catalog\Helper\Product\Edit\Action\Attribute
{
    public function getAttributes()
    {
        if ($this->_attributes === null) {
            $this->_attributes = $this->_eavConfig->getEntityType(
                \Magento\Catalog\Model\Product::ENTITY
            )->getAttributeCollection()->addIsNotUniqueFilter()->setInAllAttributeSetsFilter(
                $this->getProductsSetIds()
            );

            if ($this->_excludedAttributes) {
                $this->_attributes->addFieldToFilter('attribute_code', ['nin' => $this->_excludedAttributes]);
            }

            // check product type apply to limitation and remove attributes that impossible to change in mass-update
            $productTypeIds = $this->getProducts()->getProductTypeIds();
            foreach ($this->_attributes as $attribute) {
                /* @var $attribute \Magento\Catalog\Model\Entity\Attribute */
                foreach ($productTypeIds as $productTypeId) {
                    $applyTo = $attribute->getApplyTo();
                    if (count($applyTo) > 0 && !in_array($productTypeId, $applyTo)) {
                        $this->_attributes->removeItemByKey($attribute->getId());
                        break;
                    }
                    /**
                     * Magento\Config\Model\Config\Source\Yesno aren't allowed
                     * as they do not extend \Magento\Framework\Data\Form\Element\AbstractElement
                     */
                    if ($attribute->getFrontendInputRenderer() === 'Magento\Config\Model\Config\Source\Yesno') {
                        $this->_attributes->removeItemByKey($attribute->getId());
                        break;
                    }
                }
            }
        }

        return $this->_attributes;
    }
}
