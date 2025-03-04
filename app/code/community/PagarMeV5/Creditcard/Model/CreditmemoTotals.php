<?php

class PagarMeV5_Creditcard_Model_CreditmemoTotals extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract {

	/**
	 * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
	 *
	 * @return PagarMeV5_Creditcard_Model_CreditmemoTotals
	 */
	public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo) {
		$order = $creditmemo->getOrder();
		$transaction = \Mage::getModel('pagarmev5_core/service_order')
			->getTransactionByOrderId(
				$order->getId()
			);
		$creditmemo->setGrandTotal(
			$creditmemo->getGrandTotal() + $transaction->getRateAmount()
		);
		$creditmemo->setBaseGrandTotal(
			$creditmemo->getBaseGrandTotal() + $transaction->getRateAmount()
		);

		return $this;
	}
}
