<?php

##eloom.licenca##

$installer = $this;
$installer->startSetup();
$conn = $installer->getConnection();

$salesOrderTable = $installer->getTable('sales/order');
if (!$conn->tableColumnExists($salesOrderTable, 'pagarmev5_interest_amount')) {
	$conn->addColumn($salesOrderTable, 'pagarmev5_interest_amount', 'DECIMAL(10,4) NOT NULL');
}
if (!$conn->tableColumnExists($salesOrderTable, 'pagarmev5_base_interest_amount')) {
	$conn->addColumn($salesOrderTable, 'pagarmev5_base_interest_amount', 'DECIMAL(10,4) NOT NULL');
}

$quoteTableAddress = $installer->getTable('sales/quote_address');
if (!$conn->tableColumnExists($quoteTableAddress, 'pagarmev5_interest_amount')) {
	$conn->addColumn($quoteTableAddress, 'pagarmev5_interest_amount', 'DECIMAL(10,4) NOT NULL');
}
if (!$conn->tableColumnExists($quoteTableAddress, 'pagarmev5_base_interest_amount')) {
	$conn->addColumn($quoteTableAddress, 'pagarmev5_base_interest_amount', 'DECIMAL(10,4) NOT NULL');
}

$invoiceTable = $installer->getTable('sales/invoice');
if (!$conn->tableColumnExists($invoiceTable, 'pagarmev5_interest_amount')) {
	$conn->addColumn($invoiceTable, 'pagarmev5_interest_amount', 'DECIMAL(10,4) NOT NULL');
}
if (!$conn->tableColumnExists($invoiceTable, 'pagarmev5_base_interest_amount')) {
	$conn->addColumn($invoiceTable, 'pagarmev5_base_interest_amount', 'DECIMAL(10,4) NOT NULL');
}

$installer->endSetup();