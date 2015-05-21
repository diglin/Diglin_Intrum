<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin_Intrum
 * @package     Diglin_Intrum
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */ 
/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;

$installer->startSetup();

$entityType = Mage_Sales_Model_Order::ENTITY;
$installer->addAttribute($entityType, 'intrum_status', array('type' => Varien_Db_Ddl_Table::TYPE_TEXT));
$installer->addAttribute($entityType, 'intrum_credit_rating', array('type' => Varien_Db_Ddl_Table::TYPE_TEXT));
$installer->addAttribute($entityType, 'intrum_credit_level', array('type' => Varien_Db_Ddl_Table::TYPE_TEXT));

$installer->getConnection()->dropTable($installer->getTable('diglin_intrum/log'));
$installer->getConnection()->dropTable($installer->getTable('intrum'));

$logTable = $installer->getTable('diglin_intrum/log');

$tableLog = $installer->getConnection()->newTable($logTable);
$tableLog
    ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 4, array('primary' => true, 'auto_increment' => true, 'nullable' => false, 'unsigned' => true))
    ->addColumn('firstname', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('lastname', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('company', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('street', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('postcode', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('town', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('country', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('ip', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('request_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array('nullable' => true))
    ->addColumn('error', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true))
    ->addColumn('response', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true))
    ->addColumn('request', Varien_Db_Ddl_Table::TYPE_TEXT, null, array('nullable' => true))
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array('nullable' => true, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT_UPDATE))
    ->setComment('Log for Intrum requests');

$installer->getConnection()->createTable($tableLog);

$installer->endSetup();