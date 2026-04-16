<?php

class PagarMeV5_Boleto_Block_Success extends Mage_Checkout_Block_Onepage_Success {

	public function getPayment() {
		//$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		//$order = Mage::getModel('sales/order')->load($orderId);

		$order = Mage::getModel('sales/order')->loadByIncrementId(
			$this->getOrderId()
		);

		return $order->getPayment();
	}

	/**
	 * @return bool
	 */
	public function isBoletoPayment() {
		$method = $this->getPayment()->getMethodInstance()->getCode();
		if ($method == PagarMeV5_Boleto_Model_Boleto::BOLETO) {
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getBoletoUrl() {
		$additionalInfo = (array)$this->getPayment()->getAdditionalInformation();

		//return $additionalInfo['pagarme_boleto_url'];
		return null;
	}
}
