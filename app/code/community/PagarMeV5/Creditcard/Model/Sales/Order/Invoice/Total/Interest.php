<?php

use PagarMeV5_Creditcard_Model_Installments as Installments;

class PagarMeV5_Creditcard_Model_Sales_Order_Invoice_Total_Interest extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	/**
	 * @var Mage_Sales_Model_Order
	 */
	protected $order;

	/**
	 * @param Mage_Sales_Model_Order_Invoice $invoice
	 *
	 * @return PagarMeV5_Creditcard_Model_InvoiceTotals
	 */
	public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
		$this->order = $invoice->getOrder();

		$transaction = \Mage::getModel('pagarmev5_core/service_order')
			->getTransactionByOrderId(
				$this->order->getId()
			);

		if ($this->shouldUpdateRateAmount($transaction)) {
			$transaction->setRateAmount(
				$this->updateRateAmount($transaction, $invoice)
			);
		}

		$invoice->setGrandTotal(
			$invoice->getGrandTotal() + $transaction->getRateAmount()
		);
		$invoice->setBaseGrandTotal(
			$invoice->getBaseGrandTotal() + $transaction->getRateAmount()
		);

		return $this;
	}

	/**
	 * @param PagarMeV5_Core_Model_Transaction $transaction
	 * @param Mage_Sales_Model_Order_Invoice $invoice
	 *
	 * @return float
	 */
	private function updateRateAmount(
		PagarMeV5_Core_Model_Transaction $transaction,
		Mage_Sales_Model_Order_Invoice $invoice
	) {
		$sdk = Mage::getModel('pagarmev5_core/sdk_adapter')
			->getSdk();

		$orderTotal =
			$invoice->getGrandTotal() + $this->order->getShippingAmount();

		$installments = new Installments(
			Mage::helper('pagarmev5_core')->parseAmountToCents($orderTotal),
			$transaction->installments,
			$this->getFreeInstallmentStoreConfig(),
			$transaction->interest_rate,
			$this->getMaxInstallmentStoreConfig(),
			$sdk
		);

		$updatedRateAmount = Mage::helper('pagarmev5_core')
			->parseAmountToCurrency($installments->getRateAmount());

		$writeConnection = Mage::getSingleton('core/resource')
			->getConnection('core_write');

		$updateRateAmountQuery = sprintf(
			'UPDATE pagarmev5_transaction SET %s',
			'rate_amount = :rateAmount WHERE order_id = :orderId;'
		);

		$queryValues = [
			'rateAmount' => $updatedRateAmount,
			'orderId' => $this->order->getId()
		];

		$writeConnection->query($updateRateAmountQuery, $queryValues);

		return $updatedRateAmount;
	}

	/**
	 * @param PagarMeV5_Core_Model_Transaction $transaction
	 *
	 * @return bool
	 */
	private function shouldUpdateRateAmount(
		PagarMeV5_Core_Model_Transaction $transaction
	) {
		return (bool)$transaction->getInterestRate();
	}
}
