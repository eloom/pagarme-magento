<?php

use PagarmeCoreApiLib\PagarmeCoreApiClient;

class PagarMe_Core_Model_Sdk_Adapter extends Mage_Core_Model_Abstract {

	/**
	 * @var PagarmeCoreApiClient
	 */
	private $sdk;

	public function _construct() {
		parent::_construct();

		$apiKey = Mage::getStoreConfig('payment/pagarme/api_key');
		$this->sdk = new PagarmeCoreApiClient($apiKey, null, null);
	}

	/**
	 * @return PagarmeCoreApiClient
	 */
	public function getSdk() {
		return $this->sdk;
	}
}
