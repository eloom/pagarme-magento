<?php

use PagarmeCoreApiLib\Models\GetOrderResponse;

class PagarMe_Boleto_Model_UnpaidBoleto {
	private $logger;

	public function __construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
	}

	/**
	 * Returns configured timezone on platform
	 *
	 * @return string
	 */
	private function getCurrentTimezone() {
		return Mage::getStoreConfig('general/locale/timezone');
	}

	/**
	 * Filter expired boletos
	 *
	 * @return Varien_Object
	 */
	private function expiredBoletos() {
		$today = new DateTime(
			'now',
			new DateTimeZone($this->getCurrentTimezone())
		);
		$expiredBoletos = $today->modify('-7 days');

		$boletosFilter = Mage::getModel('pagarme_core/transaction')->getCollection();

		$boletosFilter->addFieldToFilter(
			'boleto_expiration_date',
			['lt' => $expiredBoletos->format('Y-m-d H:i:s')]
		);

		return $boletosFilter->getItems();
	}

	/**
	 * Retrieve an order
	 *
	 * @param int $orderId
	 *
	 * @return Mage_Sales_Model_Order
	 */
	private function loadOrder($orderId) {
		return Mage::getModel('sales/order')->load($orderId);
	}

	/**
	 * @param int $orderId
	 * @return GetOrderResponse
	 */
	private function loadBoletoTransaction($orderId) {
		$sdk = Mage::getModel('pagarme_core/sdk_adapter')->getSdk();

		return $sdk->getOrders()->getOrder($orderId);
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @param GetOrderResponse $orderResponse
	 */
	private function cancelOrder(Mage_Sales_Model_Order $order, GetOrderResponse $orderResponse) {
		if ($order->getState() === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
			$cancelHandler = new PagarMe_Core_Model_OrderStatusHandler_UnpaidBoleto(
				$order,
				$orderResponse
			);

			$cancelHandler->handleStatus();
		}
	}

	/**
	 * Cancel orders from unpaid boleto
	 *
	 * @return void
	 */
	public function cancel() {
		$expiredBoletos = $this->expiredBoletos();

		foreach ($expiredBoletos as $expiredBoleto) {
			try {
				$order = $this->loadOrder($expiredBoleto->getOrderId());
				$orderResponse = $this->loadBoletoTransaction($expiredBoleto->getTransactionId());

				$this->cancelOrder($order, $orderResponse);
			} catch (\Exception $e) {
				$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
			}
		}
	}
}
