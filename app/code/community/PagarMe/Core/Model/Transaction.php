<?php

class PagarMe_Core_Model_Transaction extends Mage_Core_Model_Abstract
{
    use PagarMe_Core_Trait_ConfigurationsAccessor;

    /**
     * @return type
     */
    public function _construct()
    {
        return $this->_init('pagarme_core/transaction');
    }

    /**
     * Creates a hash to be used as reference key
     *
     * @return string
     */
    public function getReferenceKey()
    {
        return md5(uniqid(rand()));
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $transaction
     *
     * @return void
     */
    private function saveCreditCardInformation($order, $transaction)
    {
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());

        $installments = $transaction->installments;
        $interestRate = $this->getInterestRateStoreConfig();

        $subtotalWithDiscount = $quote->getData()['subtotal_with_discount'];
        $shippingAmount = $quote->getShippingAddress()->getShippingAmount();

        $amountWithoutInterestRate = $shippingAmount + $subtotalWithDiscount;
        $amountWithInterestRate = $quote->getData()['grand_total'];

        $rateAmount = $amountWithInterestRate - $amountWithoutInterestRate;

        $order->setInterestAmount($rateAmount);

        $this
            ->setInstallments($installments)
            ->setInterestRate($interestRate)
            ->setRateAmount($rateAmount);
    }

    /**
     * @param stdClass $transaction
     *
     * @return void
     */
    private function saveBoletoInformation($transaction)
    {
        $this->setBoletoExpirationDate($transaction->boleto_expiration_date);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $infoInstance
     * @param string $referenceKey
     * @param stdClass $transaction
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function saveTransactionInformation(
        Mage_Sales_Model_Order $order,
                               $infoInstance,
                               $referenceKey,
        stdClass               $transaction = null
    )
    {
        $this
            ->setReferenceKey($referenceKey)
            ->setOrderId($order->getId());

        if (!is_null($transaction)) {
            $totalAmount = Mage::helper('pagarme_core')->parseAmountToCurrency($transaction->amount);

            $this->setTransactionId($transaction->id)
                ->setPaymentMethod($transaction->payment_method)
                ->setFutureValue($totalAmount);

            if ($transaction->payment_method == 'credit_card') {
                $this->saveCreditCardInformation($order, $transaction);
            } else if ($transaction->payment_method == 'pix') {
                $this->setPixQrCode($transaction->pix_qr_code);
                $this->setPixExpirationDate($transaction->pix_expiration_date);
            } else if ($transaction->payment_method == 'boleto') {
                $this->saveBoletoInformation($transaction);
            }
        }

        $this->save();
    }
}
