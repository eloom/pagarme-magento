<?php

class PagarMeV5_Creditcard_Model_Sales_Order_Invoice_Total_Interest extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

	protected $_code = 'pagarmev5_interest';

	public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
		parent::collect($invoice);
		$order = $invoice->getOrder();
		$baseTotalInterestAmount = $order->getPagarmev5BaseInterestAmount();
		$totalInterestAmount = Mage::app()->getStore()->convertPrice($baseTotalInterestAmount);

		$invoice->setPagarmev5InterestAmount($totalInterestAmount);
		$invoice->setPagarmev5BaseInterestAmount($baseTotalInterestAmount);

		$invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getPagarmev5InterestAmount());
		$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getPagarmev5BaseInterestAmount());

		return $this;
	}
}
