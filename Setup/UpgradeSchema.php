<?php
namespace Antonshell\OrdersWebhooks\Setup;

use Antonshell\OrdersWebhooks\Helpers\WebhookHelper;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = WebhookHelper::TABLE_NAME;

        if (!$installer->tableExists($tableName)) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable($tableName)
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Id'
                )
                ->addColumn(
                    'event',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Event'
                )
                ->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [],
                    'Order ID'
                )
                ->addColumn(
                    'url',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    [],
                    'Webhook Url'
                )
                ->addColumn(
                    'data',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    [],
                    'Webhook Data'
                )
                ->addColumn(
                    'response',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    [],
                    'Response'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    [],
                    'Created Date'
                )
                ->addColumn(
                    'sent_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    [],
                    'Sent Date'
                )
                ->setComment('Magento2 Orders Webhooks Table');
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}