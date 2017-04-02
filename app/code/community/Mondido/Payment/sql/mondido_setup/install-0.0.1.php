<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tables = array(
    $installer->getTable('sales/quote'),
    $installer->getTable('sales/order'),
    $installer->getTable('sales/order_grid')
);

foreach ($tables as $table) {
    $installer->getConnection()->addColumn($table, 'mondido_transaction', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => '64k',
        'unsigned'  => true,
        'nullable'  => true,
        'comment'   => 'Mondido Transaction'
    ));
}

$installer->endSetup();

