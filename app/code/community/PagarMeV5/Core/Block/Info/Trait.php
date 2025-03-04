<?php

use PagarmeCoreApiLib\Models\GetOrderResponse;

trait PagarMeV5_Core_Block_Info_Trait {
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

		$pagarmeInfosRelated = \Mage::getModel('pagarmev5_core/service_order')->getInfosRelatedByOrderId($order->getId());

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
		return \Mage::getModel('pagarmev5_core/sdk_adapter')
			->getSdk()
			->getOrders()
			->getOrder($orderId);
	}
}
