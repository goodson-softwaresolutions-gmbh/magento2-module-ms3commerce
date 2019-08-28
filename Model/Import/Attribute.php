<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Model\Import;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\InputException;
use Staempfli\CommerceImport\Api\Data\AttributeImportInterface;
use Staempfli\CommerceImport\Model\Reader\Attribute as AttributeReader;
use Staempfli\CommerceImport\Model\AbstractImport;
use Staempfli\CommerceImport\Model\Utils\Import as ImportUtils;

class Attribute extends AbstractImport implements AttributeImportInterface
{
    /**
     * @var int
     */
    private $totalAttributes = 0;
    /**
     * @var array
     */
    private $attributes = [];
    /**
     * @var array
     */
    private $attributeSets = [];
    /**
     * @var array
     */
    private $attributeGroups = [];
    /**
     * @var array
     */
    private $lockedAttributes = [];
    /**
     * @var array
     */
    private $scopeMapping = [
        ScopedAttributeInterface::SCOPE_STORE => 'store',
        ScopedAttributeInterface::SCOPE_WEBSITE => 'website',
        ScopedAttributeInterface::SCOPE_GLOBAL => 'global'
    ];
    /**
     * @var array
     */
    private $entityMapping = [
        Product::ENTITY => 'product',
        Category::ENTITY => 'category'
    ];
    /**
     * @var AttributeReader
     */
    private $attributeReader;

    /**
     * Attribute constructor.
     * @param AttributeReader $attributeReader
     * @param ImportUtils $importUtils
     */
    public function __construct(
        AttributeReader $attributeReader,
        ImportUtils $importUtils
    ) {
        parent::__construct($importUtils);
        $this->attributeReader = $attributeReader;
    }

    /**
     * Set Attributes to import
     *
     * @return array
     */
    public function prepare()
    {
        $this->attributes = $this->attributeReader->fetch();
        $this->prepareAttributes();
        return $this->attributes;
    }

    /**
     * Prepare attributes to be imported
     */
    protected function prepareAttributes()
    {
        foreach ($this->attributes as $entity => $attribute) {
            foreach ($attribute as $code => $row) {
                $this->totalAttributes++;
                if (isset($row['_sets'])) {
                    foreach ($row['_sets'] as $set) {
                        $this->prepareAttributeGroups($set, $entity);
                        $this->prepareAttributeSets($set, $entity, $row, $code);
                    }
                }
            }
        }
    }

    /**
     * @param $set
     * @param $entity
     */
    protected function prepareAttributeGroups($set, $entity)
    {
        $this->attributeGroups[$entity][$set['group']]['_sets'][$set['attribute_set_code']] = $set['attribute_set_code']; //@codingStandardsIgnoreLine
    }

    /**
     * @param $set
     * @param $entity
     * @param $row
     * @param $code
     */
    protected function prepareAttributeSets($set, $entity, $row, $code)
    {
        $this->attributeSets[$entity][$set['attribute_set_code']]['_groups'][$set['group']]['position'] = $set['position']; //@codingStandardsIgnoreLine
        $this->attributeSets[$entity][$set['attribute_set_code']]['_groups'][$set['group']]['_attributes'][$code] = $row['_data']; //@codingStandardsIgnoreLine
    }

    /**
     * Validate data before import
     *
     * @throws \Exception
     */
    public function validate()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Validate attributes');
        foreach ($this->attributes as $entity => $attributes) {
            $this->validateAttributes($entity, $attributes);
        }
        $this->getImportUtils()->getConsoleOutput()->info('Attribute validation done');
    }

    /**
     * Import Attributes
     */
    public function import()
    {
        $this->getImportUtils()->getConsoleOutput()->title('Import attribute sets');
        $this->importAttributeSets();

        $this->getImportUtils()->getConsoleOutput()->title('Import attributes');
        $this->importAttributes();
    }

    /**
     * Clean attributes after import
     */
    public function clearMemory()
    {
        $this->attributes = [];
        $this->attributeSets = [];
        $this->attributeGroups = [];
        $this->lockedAttributes = [];
    }

    /**
     * Import attribute sets
     */
    public function importAttributeSets()
    {
        foreach ($this->attributeSets as $entity => $sets) {
            if ($entity === Category::ENTITY) {
                continue;
            }

            foreach ($sets as $setName => $setData) {
                $this->addAttributeSet($entity, $setName);
                $this->addAttributeGroup($setData, $entity, $setName);
            }
        }
    }

    /**
     * Import attributes
     */
    public function importAttributes()
    {
        $outputHeaders = ['code', 'entity', 'scope', 'searchable', 'comparable', 'visible', 'filterable'];
        $outputRows = [];
        $this->getImportUtils()->getConsoleOutput()->startProgress($this->totalAttributes);
        foreach ($this->attributes as $entity => $attributes) {
            foreach ($attributes as $attributeName => $attributeData) {
                $this->getImportUtils()->getConsoleOutput()->advanceProgress();
                if (!$this->shouldAddAttribute($attributeName)) {
                    $this->getImportUtils()
                        ->getConsoleOutput()
                        ->comment(sprintf('Attribute "%s" Skipped', $attributeName));
                    continue;
                }
                $outputRows[] = $this->addAttribute($attributeData, $entity, $attributeName);
                $this->assignAttribute($attributeData, $entity, $attributeName);
            }
        }
        $this->getImportUtils()->getConsoleOutput()->finishProgress();
        $this->getImportUtils()->getConsoleOutput()->table($outputHeaders, $outputRows);
    }

    /**
     * @param string $attributeName
     * @return bool
     */
    public function shouldAddAttribute(string $attributeName)
    {
        $existingAttribute = $this->getImportUtils()
            ->getAttributeUtils()
            ->getAttribute($attributeName, Product::ENTITY);

        if (!$existingAttribute->getId()) {
            return true;
        }
        if ($existingAttribute->getData('ms3_imported')) {
            return true;
        }
        return false;
    }

    public function validateAttributes(string $entity, array $attributes)
    {
        foreach (array_keys($attributes) as $code) {
            if ($this->getImportUtils()
                ->getAttributeUtils()
                ->isSystemAttribute($code, $entity)
            ) {
                $this->getImportUtils()->getConsoleOutput()->comment($code . ' is a system attribute!');
                unset($this->attributes[$entity][$code]);
            }
            if (strlen($code) > \Magento\Eav\Model\Entity\Attribute::ATTRIBUTE_CODE_MAX_LENGTH) {
                $this->getImportUtils()->getConsoleOutput()->comment(
                    sprintf(
                        'An attribute code must not be more than %s characters. [%s]',
                        \Magento\Eav\Model\Entity\Attribute::ATTRIBUTE_CODE_MAX_LENGTH,
                        $code
                    )
                );
                unset($this->attributes[$entity][$code]);
            }
        }
    }

    protected function addAttributeSet(string $entity, string $setName)
    {
        try {
            $this->getImportUtils()->getAttributeSetUtils()->addAttributeSet($entity, $setName);
            $this->getImportUtils()->getConsoleOutput()->info(sprintf('Attribute Set [%s] created!', $setName));
        } catch (InputException $e) {
            $this->getImportUtils()->getConsoleOutput()->comment($e->getMessage());
        }
    }

    protected function addAttributeGroup(array $setData, string $entity, string $setName)
    {
        if (isset($setData['_groups'])) {
            foreach (array_keys($setData['_groups']) as $groupName) {
                $setId = $this->getImportUtils()->getAttributeSetUtils()->getAttributeSetId($entity, $setName);
                $this->getImportUtils()->getAttributeGroupUtils()->addAttributeGroup($entity, $setId, $groupName);
            }
        }
    }

    protected function addAttribute(array $attributeData, string $entity, string $attributeName): array
    {
        $data = $attributeData['_data'];
        $this->getImportUtils()->getAttributeUtils()->addAttribute($attributeName, $entity, $data);
        return $this->getOutputRow($entity, $attributeName, $data);
    }

    protected function getOutputRow(string $entity, string $code, array $data = []): array
    {
        return [
            $code,
            isset($this->entityMapping[$entity]) ? $this->entityMapping[$entity] : $entity,
            isset($this->scopeMapping[$data['global']]) ? $this->scopeMapping[$data['global']] : $data['global'],
            $data['searchable'],
            $data['comparable'],
            $data['visible_on_front'],
            $data['filterable']
        ];
    }

    protected function assignAttribute(array $attributeData, string $entity, string $attributeName)
    {
        if (isset($attributeData['_sets'])) {
            foreach ($attributeData['_sets'] as $setData) {
                $set = $setData['attribute_set_code'] ?? 'Default';
                $group = $setData['group'] ?? 'General';
                $sort = $setData['position'] ?? 0;
                $this->getImportUtils()->getAttributeUtils()->assign($entity, $set, $group, $attributeName, $sort);
            }
        }
    }

    public function setMarketAndLangId($marketId, $langId)
    {
        $this->attributeReader->setMarketAndLangId($marketId, $langId);
    }
}
