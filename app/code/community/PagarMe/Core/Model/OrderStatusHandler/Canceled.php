<?php

class PagarMe_Core_Model_OrderStatusHandler_Canceled extends PagarMe_Core_Model_OrderStatusHandler_Base
{
    private $logger;

    /**
     * @var string Message to be displayed on Order's history comments
     */
    private $cancelMessage;

    /**
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $transaction
     * @param string $cancelMessage
     */
    public function __construct(Mage_Sales_Model_Order $order, stdClass $transaction, $cancelMessage) {
        $this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
        $this->cancelMessage = $cancelMessage;
        parent::__construct($order, $transaction);
    }

    /**
     * Cancel an order with custom message
     *
     * @throws \Mage_Core_Exception
     */
    private function cancel()
    {
        if ($this->order->canCancel()) {
            $cancelMessage = Mage::helper('pagarme_core')
                ->__($this->cancelMessage);

            $this->order->getPayment()->cancel();
            $this->order->registerCancellation($cancelMessage);

            Mage::dispatchEvent(
                'order_cancel_after',
                ['order' => $this->order]
            );
        }
    }

    /**
     * Responsible to handle order status based on transaction status
     */
    public function handleStatus()
    {
        $magentoTransaction = Mage::getModel('core/resource_transaction');

        try {
            if ($this->order->getState() === Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                /**
                 * Cannot cancel order's with Payment Review State.
                 * So we move the order to Pending Payment before cancel it.
                 */
                $this->order->setState(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    false,
                    Mage::helper('pagarme_core')
                        ->__('Review finished. Cancelling the order.'),
                    false
                );
            }

            $this->cancel();
            $magentoTransaction->addObject($this->order)->save();
        } catch (\Exception $e) {
            $this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
        }
        
        return $this->order;
    }
}
