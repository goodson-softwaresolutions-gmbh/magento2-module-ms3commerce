<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */
namespace Staempfli\CommerceImport\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Staempfli\CommerceImport\Model\Utils\Attribute\Attribute as AttributeUtils;
use Staempfli\CommerceImport\Setup\ImportDatabaseSetupFactory;

/**
 * Upgrade the Catalog module DB scheme
 */
class InstallSchema implements InstallSchemaInterface
{
    protected $importDatabaseSetupFactory;

    public function __construct(ImportDatabaseSetupFactory $importDatabaseSetupFactory)
    {
        $this->importDatabaseSetupFactory = $importDatabaseSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) //@codingStandardsIgnoreLine
    {
        $setup->startSetup();

        $importDatabaseSetup = $this->importDatabaseSetupFactory->create(['setup' => $setup]);
        $importDatabaseSetup->setupTables($setup);

        $this->createOptionMappingTable($setup);

        $setup->endSetup();
    }

    /**
     * @param $setup
     */
    protected function createOptionMappingTable(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'commerce_import_option_mapping'
         * @var $setup \Magento\Setup\Module\Setup
         */
        if ($setup->getConnection()
            ->isTableExists($setup->getTable(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE))) {
            $setup->getConnection()->dropTable($setup->getTable(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE));
        }

        $table = $setup->getConnection()->newTable(
            $setup->getTable(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE)
        )->addColumn(
            'entity_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'option_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Option Id'
        )->addColumn(
            'attribute_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Attribute Id'
        )->addColumn(
            'mapping_id',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Mapping Id'
        )->addIndex(
            $setup->getIdxName(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE, ['option_id']),
            ['option_id']
        )->addForeignKey(
            $setup->getFkName(
                AttributeUtils::IMPORT_OPTION_MAPPING_TABLE,
                'option_id',
                'eav_attribute_option_value',
                'option_id'
            ),
            'option_id',
            $setup->getTable('eav_attribute_option_value'),
            'option_id',
            Table::ACTION_CASCADE
        )->setComment(
            'Commerce Import Option Mapping'
        );
        $setup->getConnection()->createTable($table);
    }
}
