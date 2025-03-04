<?php

class PagarMeV5_Creditcard_Block_Sales_InvoiceRateAmount extends PagarMeV5_Creditcard_Block_Sales_RateAmount {
	protected function getReferencedOrder() {
		$invoice = parent::getReferencedOrder();
		return $invoice->getOrder();
	}
}
