<?php

class PagarMeV5_Core_Model_PostbackHandler_Refused extends PagarMeV5_Core_Model_PostbackHandler_Base {
	const MAGENTO_DESIRED_STATE = Mage_Sales_Model_Order::STATE_CANCELED;

	/**
	 * Returns the desired state on magento
	 * @return string
	 * @see    PagarMeV5_Core_Model_OrderStatusHandler_Canceled
	 * @deprecated
	 *
	 */
	protected function getDesiredState() {
		return self::MAGENTO_DESIRED_STATE;
	}

	private function retrieveTransaction() {
		$sdk = Mage::getModel('pagarmev5_core/sdk_adapter')
			->getSdk();

		return $sdk->transactions()->get(['id' => $this->transactionId]);
	}

	/**
	 * @return \Mage_Sales_Model_Order
	 */
	public function process() {
		$transaction = $this->retrieveTransaction();

		$canceledHandler = new PagarMeV5_Core_Model_OrderStatusHandler_Canceled(
			$this->order,
			$transaction,
			$this->buildRefusedReasonMessage($transaction->refuse_reason)
		);
		$canceledHandler->handleStatus();

		return $this->order;
	}

	/**
	 * Returns refuse message sent by Pagar.me API
	 *
	 * @param string $refuseReason
	 *
	 * @return string
	 */
	private function buildRefusedReasonMessage($refuseReason) {
		return sprintf(
			'Refused by %s',
			$refuseReason
		);
	}
}
