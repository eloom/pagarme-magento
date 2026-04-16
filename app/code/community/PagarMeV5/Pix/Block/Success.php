<?php
class PagarMeV5_Pix_Block_Success extends Mage_Checkout_Block_Onepage_Success {

	public function getPayment() {
//		$orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
//		$order = Mage::getModel('sales/order')->load($orderId);

		$order = Mage::getModel('sales/order')->loadByIncrementId(
			$this->getOrderId()
		);

		return $order->getPayment();
	}

	/**
	 * @return bool
	 */
	public function isPixPayment() {
		$method = $this->getPayment()->getMethodInstance()->getCode();
		if ($method == PagarMeV5_Pix_Model_Pix::PIX) {
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getQrCode() {
		$order = Mage::getModel('sales/order')->loadByIncrementId(
			$this->getOrderId()
		);
		$pagarmeInfosRelated = \Mage::getModel('pagarmev5_core/service_order')->getInfosRelatedByOrderId($order->getId());
		$transactionId = $pagarmeInfosRelated->getTransactionId();
		$orderResponse = $this->fetchPagarmeOrderFromAPi($transactionId);

		return $orderResponse->charges[0]->lastTransaction->qrCode;
	}

	private function fetchPagarmeOrderFromAPi($orderId) {
		return \Mage::getModel('pagarmev5_core/sdk_adapter')
			->getSdk()
			->getOrders()
			->getOrder($orderId);
	}
}
