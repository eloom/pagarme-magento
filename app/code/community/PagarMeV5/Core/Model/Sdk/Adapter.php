<?php

use PagarmeCoreApiLib\PagarmeCoreApiClient;

class PagarMeV5_Core_Model_Sdk_Adapter extends Mage_Core_Model_Abstract {

	/**
	 * @var PagarmeCoreApiClient
	 */
	private $sdk;

	public function _construct() {
		parent::_construct();

		//$accountId = Mage::getStoreConfig('payment/pagarmev5/account_id');
		//$publicKey = Mage::getStoreConfig('payment/pagarmev5/public_key');
		$basicAuthUserName = Mage::getStoreConfig('payment/pagarmev5/api_key');

		$this->sdk = new PagarmeCoreApiClient($basicAuthUserName, null, null);
	}

	/**
	 * @return PagarmeCoreApiClient
	 */
	public function getSdk() {
		return $this->sdk;
	}
}
