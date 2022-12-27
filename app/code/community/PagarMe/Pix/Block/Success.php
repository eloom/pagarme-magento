<?php

class PagarMe_Pix_Block_Success extends Mage_Checkout_Block_Onepage_Success
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @codeCoverageIgnore
     */
    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->order = Mage::getModel('sales/order')->loadByIncrementId(
                $this->getOrderId()
            );
        }

        return $this->order;
    }

    /**
     * @return bool
     */
    public function isPixPayment()
    {
        $order = $this->getOrder();
        $additionalInfo = $order->getPayment()->getAdditionalInformation();
        $paymentMethod = null;
        if(array_key_exists('pagarme_payment_method', $additionalInfo)) {
            $paymentMethod = $additionalInfo['pagarme_payment_method'];
        }

        if ($paymentMethod === PagarMe_Pix_Model_Pix::PIX) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getQrCode()
    {
        $order = $this->getOrder();

        $additionalInfo = $order->getPayment()->getAdditionalInformation();
        
        return $additionalInfo['pagarme_pix_qrcode'];
    }
}
