<?php

class PagarMeV5_Core_Model_Resource_Transaction extends Mage_Core_Model_Mysql4_Abstract {
	public function _construct() {
		$this->_init('pagarmev5_core/transaction', 'order_id, transaction_id');
	}
}