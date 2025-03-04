<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
	->newTable($installer->getTable('pagarmev5_transaction'))
	->addColumn(
		'order_id',
		Varien_Db_Ddl_Table::TYPE_BIGINT,
		null,
		[
			'unsigned' => true,
			'nullable' => false,
			'primary' => true
		]
	)
	->addColumn(
		'transaction_id',
		Varien_Db_Ddl_Table::TYPE_VARCHAR,
		50,
		[
			'unsigned' => true,
			'nullable' => false,
			'primary' => true
		]
	)
	->addColumn(
		'reference_key',
		Varien_Db_Ddl_Table::TYPE_TEXT,
		255,
		[
			'nullable' => true
		]
	)
	->addColumn(
		'installments',
		Varien_Db_Ddl_Table::TYPE_SMALLINT,
		1,
		[
			'nullable' => false,
		]
	)
	->addColumn(
		'interest_rate',
		Varien_Db_Ddl_Table::TYPE_FLOAT,
		null
	)
	->addColumn(
		'future_value',
		Varien_Db_Ddl_Table::TYPE_FLOAT,
		null
	)
	->addColumn(
		'rate_amount',
		Varien_Db_Ddl_Table::TYPE_FLOAT,
		null
	)
	->addColumn(
		'payment_method',
		Varien_Db_Ddl_Table::TYPE_VARCHAR,
		50,
		[
			'nullable' => false
		]
	)
	->addColumn(
		'boleto_expiration_date',
		Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
		[
			'nullable' => true
		]
	);

$installer->getConnection()->createTable($table);

$installer->endSetup();
