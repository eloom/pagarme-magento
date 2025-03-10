<?php

class PagarMeV5_Core_Model_Postback extends Mage_Core_Model_Abstract {

	/**
	 * @var PagarMeV5_Core_Model_Service_Order
	 */
	protected $orderService;

	/**
	 * @var PagarMeV5_Core_Model_Service_Invoice
	 */
	protected $invoiceService;

	/**
	 * @codeCoverageIgnore
	 * @return PagarMeV5_Core_Model_Service_Order
	 */
	public function getOrderService() {
		if (is_null($this->orderService)) {
			$this->orderService = Mage::getModel('pagarmev5_core/service_order');
		}

		return $this->orderService;
	}

	/**
	 * @codeCoverageIgnore
	 * @param PagarMeV5_Core_Model_Service_Order $orderService
	 * @return void
	 */
	public function setOrderService(PagarMeV5_Core_Model_Service_Order $orderService) {
		$this->orderService = $orderService;
	}

	/**
	 * @codeCoverageIgnore
	 * @return PagarMeV5_Core_Model_Service_Invoice
	 */
	public function getInvoiceService() {
		if (is_null($this->invoiceService)) {
			$this->invoiceService = Mage::getModel('pagarmev5_core/service_invoice');
		}

		return $this->invoiceService;
	}

	/**
	 * @codeCoverageIgnore
	 * @param PagarMeV5_Core_Model_Service_Invoice $invoiceService
	 * @return void
	 */
	public function setInvoiceService(PagarMeV5_Core_Model_Service_Invoice $invoiceService) {
		$this->invoiceService = $invoiceService;
	}

	/**
	 * @param int $transactionId
	 * @param string $status
	 * @param string $type
	 *
	 * @return Mage_Sales_Model_Order
	 * @throws Exception|PagarMeV5_Core_Model_PostbackHandler_Exception
	 */
	public function processPostback($transactionId, $status, $type) {
		$order = $this->getOrderService()
			->getOrderByTransactionId($transactionId);

		$postbackHandler = PagarMeV5_Core_Model_PostbackHandler_Factory::createFromDesiredStatus(
			$status,
			$type,
			$order,
			$transactionId
		);

		return $postbackHandler->process();
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 * @deprecated
	 * @see PagarMeV5_Core_Model_PostbackHandler_Paid::process()
	 *
	 */
	public function setOrderAsPaid($order) {
		$invoice = $this->getInvoiceService()->createInvoiceFromOrder($order);
		$invoice->register()->pay();

		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'pago', true);
		$invoice->sendEmail();

		Mage::getModel('core/resource_transaction')
			->addObject($order)
			->addObject($invoice)
			->save();
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 * @deprecated
	 * @see PagarMeV5_Core_Model_PostbackHandler_Authorized::process()
	 *
	 */
	public function setOrderAsAuthorized($order) {
		$order->setState(
			Mage_Sales_Model_Order::STATE_PROCESSING,
			true,
			Mage::helper('sales')->__(
				'Authorized amount of %s.',
				substr('R$' . $order->getGrandTotal(), 0, -2)
			)
		);

		$transactionSave = Mage::getModel('core/resource_transaction')
			->addObject($order)
			->save();
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 * @deprecated
	 * @see \PagarMeV5_Core_Model_PostbackHandler_Refunded::process()
	 *
	 */
	public function setOrderAsRefunded($order) {
		$orderService = Mage::getModel('sales/service_order', $order);

		$invoices = [];

		foreach ($order->getInvoiceCollection() as $invoice) {
			if ($invoice->canRefund()) {
				$invoices[] = $invoice;
			}
		}

		$transaction = Mage::getModel('core/resource_transaction');

		foreach ($invoices as $invoice) {
			$creditmemo = $orderService->prepareInvoiceCreditmemo($invoice);
			$creditmemo->setRefundRequested(true);
			$creditmemo->setOfflineRequested(true);
			$creditmemo->setPaymentRefundDisallowed(true)->register();
			$transaction->addObject($creditmemo);
		}
		$transaction->addObject($order)->save();

		return $order;
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 * @deprecated
	 * @see \PagarMeV5_Core_Model_PostbackHandler_Refused::process()
	 *
	 */
	public function setOrderAsRefused($order) {
		$transaction = Mage::getModel('core/resource_transaction');

		$order->setState(
			Mage_Sales_Model_Order::STATE_CANCELED,
			true,
			Mage::helper('pagarmev5_core')->
			__('Refused by gateway.')
		);

		$transaction->addObject($order)->save();

		return $order;
	}
}
