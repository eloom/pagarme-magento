<?php

use PagarmeCoreApiLib\Models\GetOrderResponse;

trait PagarMeV5_Core_Block_Info_Trait {
	/**
	 * @codeCoverageIgnore
	 *
	 * @return GetOrderResponse
	 * @throws \Exception
	 */
	public function getOrderResponse() {
		if (!is_null($this->orderResponse)) {
			return $this->orderResponse;
		}

		$transactionId = $this->getOrderIdFromDb();
		$this->orderResponse = $this->fetchPagarmeOrderFromAPi($transactionId);

		return $this->orderResponse;
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

	/**
	 * @return string
	 */
	public function getOrderId() {
		return $this->getOrderResponse()->id;
	}

	/**
	 * @return string
	 */
	public function getTransactionId() {
		return $this->getOrderResponse()->charges[0]->lastTransaction->id;
	}
}
