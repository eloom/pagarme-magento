<?php

use PagarmeCoreApiLib\Models\GetOrderResponse;

trait PagarMe_Core_Block_Info_Trait {
	/**
	 * @codeCoverageIgnore
	 *
	 * @return GetOrderResponse
	 * @throws \Exception
	 */
	public function getTransaction() {
		if (!is_null($this->transaction)) {
			return $this->transaction;
		}

		$transactionId = $this->getOrderIdFromDb();
		$this->transaction = $this->fetchPagarmeOrderFromAPi($transactionId);

		return $this->transaction;
	}

	/**
	 * Retrieve transaction_id from database
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function getOrderIdFromDb() {
		$order = $this->getInfo()->getOrder();

		if (is_null($order)) {
			throw new \Exception('Order doesn\'t exist');
		}

		$pagarmeInfosRelated = \Mage::getModel('pagarme_core/service_order')->getInfosRelatedByOrderId($order->getId());

		return $pagarmeInfosRelated->getTransactionId();
	}

	/**
	 * Fetch order's information from API
	 *
	 * @param int $orderId
	 *
	 * @return GetOrderResponse
	 */
	private function fetchPagarmeOrderFromAPi($orderId) {
		return \Mage::getModel('pagarme_core/sdk_adapter')
			->getSdk()
			->getOrder($orderId);
	}
}
