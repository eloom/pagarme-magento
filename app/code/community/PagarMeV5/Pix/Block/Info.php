<?php

class PagarMeV5_Pix_Block_Info extends Mage_Payment_Block_Info {
	use PagarMeV5_Core_Block_Info_Trait;

	public function __construct() {
		parent::__construct();
		$this->setTemplate('pagarme-v5/pix/info.phtml');
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getQrCode() {
		return $this->getOrderResponse()->charges[0]->lastTransaction->pix_qr_code;
	}

	/**
	 * Returns the template to be showed
	 *
	 * @return string
	 */
	public function renderView() {
		try {
			$this->getOrderResponse();
		} catch (\Exception $exception) {

		}

		return parent::renderView();
	}
}
