<?php

class PagarMe_Creditcard_Block_Footer extends Mage_Core_Block_Abstract {

	protected function _construct() {
		parent::_construct();
	}

	/**
	 * @return string
	 */
	public function getPublicKey() {
		return Mage::getStoreConfig('payment/pagarme_core/public_key');
	}
}