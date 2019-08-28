<?php
/**
 * ImportDatabaseSetup
 *
 * @copyright Copyright © 2017 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

class ImportDatabaseSetup
{
    const ATTRIBUTE_TABLE = 'm2m_attribute';
    const ATTRIBUTE_OPTION_TABLE = 'm2m_attribute_option';
    const ATTRIBUTE_SET_TABLE = 'm2m_attribute_set';
    const ATTRIBUTE_VALUE_TABLE = 'm2m_attribute_value';
    const ATTRIBUTE_VARIANT_TABLE = 'm2m_attribute_variant';
    const PRODUCT_TABLE = 'm2m_product';
    const PRODUCT_CATEGORY_TABLE = 'm2m_product_category';
    const PRODUCT_LINK_TABLE = 'm2m_product_link';
    const PRODUCT_RELATION_TABLE = 'm2m_product_relation';
    const CATEGORY_TABLE = 'm2m_category';

    protected $connection;

    public function __construct(SchemaSetupInterface $setup)
    {
        $this->connection = $setup->getConnection(CommerceImportSetupConfig::DB_CONNECTION_SETUP);
    }

    public function setupTables()
    {
        $tablesToCreate = $this->getTablesToCreate();
        $this->createTables($tablesToCreate);
    }

    protected function getTablesToCreate() : array
    {
        $tables = [];
        $tables = array_merge($tables, $this->getAttributeTables(), $this->getRelationTables());
        $tables[] = $this->getCategoryTable();
        $tables[] = $this->getProductTable();
        return $tables;
    }

    protected function createTables(array $tables)
    {
        foreach ($tables as $table) {
            $this->dropTableIfExists($table->getName());
            $this->connection->createTable($table);
        }
    }

    protected function dropTableIfExists(string $tableName)
    {
        if ($this->connection->isTableExists($this->connection->getTableName($tableName))) {
            $this->connection->dropTable($this->connection->getTableName($tableName));
        }
    }

    protected function getAttributeTables() : array
    {
        $tables = [];
        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::ATTRIBUTE_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('market_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('lang_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('entity_type', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('code', Table::TYPE_TEXT, 30, ['nullable' => false])
            ->addColumn('title', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('group', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('unit', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('scope', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('filter_type', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('field_type', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('is_searchable', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('is_advanced_search', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('is_frontend', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('is_listing', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('is_sorting', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addColumn('is_comparable', Table::TYPE_SMALLINT, 1, ['nullable' => false])
            ->addIndex('market_id', ['market_id', 'lang_id']);

        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::ATTRIBUTE_OPTION_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('attribute_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('option_id', Table::TYPE_TEXT, 45, ['nullable' => false])
            ->addColumn('option_value', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addIndex('attribute_id', ['attribute_id']);

        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::ATTRIBUTE_SET_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false])
            ->addColumn('attribute_code', Table::TYPE_TEXT, 30, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addIndex('attribute_code', ['attribute_code']);

        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::ATTRIBUTE_VALUE_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('attribute_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('product_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('category_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('value', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addIndex('attribute_id', ['attribute_id'])
            ->addIndex('product_id', ['product_id'])
            ->addIndex('category_id', ['category_id']);

        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::ATTRIBUTE_VARIANT_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('product_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('attribute_code', Table::TYPE_TEXT, 30, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addIndex('product_id', ['product_id'])
            ->addIndex('attribute_code', ['attribute_code']);

        return $tables;
    }

    protected function getCategoryTable() : Table
    {
        return $this->connection
            ->newTable($this->connection->getTableName(self::CATEGORY_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('guid', Table::TYPE_TEXT, 36, ['nullable' => false])
            ->addColumn('market_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('lang_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('parent', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('position', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('level', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('name', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('description', Table::TYPE_TEXT, null)
            ->addColumn('image', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('meta_title', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('meta_keywords', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('meta_description', Table::TYPE_TEXT, null)
            ->addColumn('url_key', Table::TYPE_TEXT, 255, ['default' => null])
            ->addIndex('guid', ['guid', 'market_id', 'lang_id'], ['type' => AdapterInterface::INDEX_TYPE_UNIQUE])
            ->addIndex('parent', ['parent']);
    }

    protected function getRelationTables() : array
    {
        $tables = [];
        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::PRODUCT_CATEGORY_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('product_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('category_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addIndex('product_id', ['product_id'])
            ->addIndex('category_id', ['category_id']);

        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::PRODUCT_LINK_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('type', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('source_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('destination_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addIndex('source_id', ['source_id'])
            ->addIndex('destination_id', ['destination_id']);

        $tables[] = $this->connection
            ->newTable($this->connection->getTableName(self::PRODUCT_RELATION_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('type', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('parent_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('child_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('sort', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addIndex('parent_id', ['parent_id'])
            ->addIndex('child_id', ['child_id']);

        return $tables;
    }

    protected function getProductTable() : Table
    {
        $productTable = $this->connection
            ->newTable($this->connection->getTableName(self::PRODUCT_TABLE))
            ->addColumn('id', Table::TYPE_INTEGER, 11, ['nullable' => false, 'primary' => true])
            ->addColumn('guid', Table::TYPE_TEXT, 36, ['nullable' => false])
            ->addColumn('market_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('lang_id', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('type', Table::TYPE_INTEGER, 11, ['nullable' => false])
            ->addColumn('attribute_set_name', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('sku', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('name', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('description', Table::TYPE_TEXT, null)
            ->addColumn('short_description', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('weight', Table::TYPE_DECIMAL, ['default' => null])
            ->addColumn('meta_title', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('meta_keywords', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('meta_description', Table::TYPE_TEXT, null)
            ->addColumn('new_from_date', Table::TYPE_TEXT, 32, ['default' => null])
            ->addColumn('new_to_date', Table::TYPE_TEXT, 32, ['default' => null])
            ->addColumn('country_of_manufacture', Table::TYPE_TEXT, 255, ['default' => null])
            ->addColumn('url_key', Table::TYPE_TEXT, 255, ['default' => null])
            ->addIndex('guid', ['guid', 'market_id', 'lang_id'], ['type' => AdapterInterface::INDEX_TYPE_UNIQUE])
            ->addIndex('attribute_set_name', ['attribute_set_name']);

        $this->addImageColumns($productTable, 10);
        return $productTable;
    }

    protected function addImageColumns(Table &$table, int $number)
    {
        for ($i=1; $i<=$number; $i++) {
            $table
                ->addColumn("image_{$i}", Table::TYPE_TEXT, 255, ['default' => null])
                ->addColumn("image_{$i}_label", Table::TYPE_TEXT, 255, ['default' => null]);
        }
    }
}
