<?php

class PagarMeV5_Creditcard_Block_Info extends Mage_Payment_Block_Info_Cc {

	use PagarMeV5_Core_Block_Info_Trait;

	public function __construct() {
		parent::__construct();
		$this->setTemplate('pagarme-v5/creditcard/info.phtml');
	}

	/**
	 * @return string
	 */
	public function getInstallments() {
		return $this->getOrderResponse()->charges[0]->lastTransaction->installments;
	}

	/**
	 * @return string
	 */
	public function getCardHolderName() {
		return $this->getOrderResponse()->charges[0]->lastTransaction->card->holderName;
	}

	/**
	 * @return string
	 */
	public function getCardBrand() {
		return $this->getOrderResponse()->charges[0]->lastTransaction->card->brand;
	}

	/**
	 * Render the block only if there's a transaction object
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
