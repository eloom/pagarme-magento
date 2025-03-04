<?php

class PagarMeV5_Creditcard_Block_Sales_RateAmount extends Mage_Core_Block_Abstract {
	/**
	 * @return $this
	 */
	public function initTotals() {
		if ($this->shouldShowTotal()) {
			$total = new Varien_Object([
				'code' => 'pagarmev5_creditcard_rate_amount',
				'field' => 'pagarmev5_creditcard_rate_amount',
				'value' => $this->getRateAmount(),
				'label' => __('Installments related Interest'),
			]);

			$this->getParentBlock()->addTotalBefore($total, 'grand_total');
		}

		return $this;
	}

	/**
	 * @return float
	 */
	protected function getRateAmount() {
		$order = $this->getReferencedOrder();

		if (!is_null($order)) {
			return Mage::getModel('pagarmev5_core/transaction')
				->load($order->getId(), 'order_id')
				->getRateAmount();
		}
	}

	protected function getReferencedOrder() {
		return $this->getParentBlock()->getSource();
	}

	protected function shouldShowTotal() {
		$referencedOrder = $this->getReferencedOrder();

		if (is_null($referencedOrder)) {
			return false;
		}

		$paymentIsPagarMeV5Creditcard = $referencedOrder->getPayment()->getMethod() ==
			PagarMeV5_Creditcard_Model_Creditcard::CC;

		$rateAmount = $this->getRateAmount();
		$rateAmountIsntZero = !is_null($rateAmount) && $rateAmount > 0;

		return $paymentIsPagarMeV5Creditcard && $rateAmountIsntZero;
	}
}
