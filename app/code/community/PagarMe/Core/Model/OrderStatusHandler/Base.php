<?php

abstract class PagarMe_Core_Model_OrderStatusHandler_Base
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var stdClass
     */
    protected $transaction;

    public function __construct(
        Mage_Sales_Model_Order $order,
        stdClass               $transaction
    )
    {
        $this->order = $order;
        $this->transaction = $transaction;
    }

    /**
     * Responsible to handle order status based on transaction status
     */
    abstract public function handleStatus();
}
