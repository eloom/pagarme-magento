<?php

class PagarMe_Core_Model_PostbackHandler_Analyzing extends PagarMe_Core_Model_PostbackHandler_Base
{
    const MAGENTO_DESIRED_STATUS = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;

    /**
     * Returns the desired state on magento
     *
     * @return string
     */
    public function getDesiredState()
    {
        return self::MAGENTO_DESIRED_STATUS;
    }

    /**
     * @return \Mage_Sales_Model_Order
     */
    public function process()
    {
        $this->order->setState(
            self::MAGENTO_DESIRED_STATUS,
            true,
            Mage::helper('pagarme_core')
                ->__('Transaction waiting for antifraud analysis')
        );

        Mage::getModel('core/resource_transaction')
            ->addObject($this->order)
            ->save();

        return $this->order;
    }
}
