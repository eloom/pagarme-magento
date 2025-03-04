<?php

use PagarmeCoreApiLib\Models\GetOrderResponse;

abstract class PagarMeV5_Core_Model_OrderStatusHandler_Base {

	/**
	 * @var Mage_Sales_Model_Order
	 */
	protected $order;

	/**
	 * @var GetOrderResponse
	 */
	protected $getOrderResponse;

	public function __construct(Mage_Sales_Model_Order $order, GetOrderResponse $getOrderResponse) {
		$this->order = $order;
		$this->getOrderResponse = $getOrderResponse;
	}

	/**
	 * Responsible to handle order status based on transaction status
	 */
	abstract public function handleStatus();
}
