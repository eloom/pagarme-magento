<?php

use PagarMeV5_Core_Model_OrderStatusHandler_Base as BaseHandler;

class PagarMeV5_Core_Model_OrderStatusHandler_UnpaidBoleto extends BaseHandler {
	/**
	 * @return string
	 */
	private function buildCancelMessage() {
		$message = sprintf(
			'Canceled due unpaid boleto. Expiration date was %s',
			$this->getOrderResponse->getBoletoExpirationDate()->format('d/m/Y')
		);
		// FIXME: revisar a data de expiração

		return Mage::helper('pagarmev5_core')->__($message);
	}

	/**
	 * Responsible to handle order status based on transaction status
	 */
	public function handleStatus() {
		$canceledHandler = new PagarMeV5_Core_Model_OrderStatusHandler_Canceled(
			$this->order,
			$this->getOrderResponse,
			$this->buildCancelMessage()
		);

		$canceledHandler->handleStatus();

		return $this->order;
	}
}
