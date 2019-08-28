<?php
/**
 * upgradeSchema
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Staempfli\CommerceImport\Model\Utils\Attribute\Attribute as AttributeUtils;
use Staempfli\CommerceImport\Setup\ConfigOptionsList as CommerceImportSetupConfig;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.7.0', '<')) {
            $this->upgradeVersionZeroSevenZero($setup);
        }

        if (version_compare($context->getVersion(), '0.8.0', '<')) {
            $this->upgradeVersionZeroEightZero($setup);
        }

        if (version_compare($context->getVersion(), '0.9.0', '<')) {
            $this->alterWeightColumnM2MTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * Upgrade script for version 0.7.0
     *
     * @param SchemaSetupInterface $setup
     */
    protected function upgradeVersionZeroSevenZero(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('eav_attribute'),
            'ms3_imported',
            [
                'type' => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Defines whether the attribute was created by ms3 import',
            ]
        );
    }

    protected function upgradeVersionZeroEightZero(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->addColumn(
                $setup->getTable(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE),
                'attribute_id',
                [
                'type' => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => true,
                'comment' => 'Attribute Id',
                ]
            );

        $optionIds = $setup->getConnection()->fetchAssoc(
            sprintf(
                "SELECT option_id FROM %s",
                $setup->getConnection()
                    ->getTableName(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE)
            )
        );

        if ($optionIds) {
            $attributeOptions = $setup->getConnection()->fetchAll(
                sprintf(
                    "SELECT option_id, attribute_id FROM %s WHERE option_id IN(%s)",
                    $setup->getConnection()
                        ->getTableName('eav_attribute_option'),
                    implode(',', array_keys($optionIds))
                )
            );

            foreach ($attributeOptions as $row) {
                $setup->getConnection()->update(
                    $setup->getConnection()
                        ->getTableName(AttributeUtils::IMPORT_OPTION_MAPPING_TABLE),
                    ['attribute_id' => $row['attribute_id']],
                    sprintf('option_id = %s', $row['option_id'])
                );
            }
        }
    }

    private function alterWeightColumnM2MTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection(CommerceImportSetupConfig::DB_CONNECTION_SETUP)
            ->changeColumn(
                ImportDatabaseSetup::PRODUCT_TABLE,
                'weight',
                'weight',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'comment' => 'weight',
                ]
            );
    }
}
