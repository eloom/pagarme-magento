<?php

use PagarMe\Sdk\Transaction\AbstractTransaction;
use PagarMe\Sdk\Transaction\CreditCardTransaction;
use PagarMe\Sdk\Transaction\BoletoTransaction;
use PagarMe\Sdk\Transaction\PixTransaction;

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
     * @param CreditCardTransaction $transaction
     *
     * @return void
     */
    private function saveCreditCardInformation($order, $transaction)
    {
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());

        $installments = $transaction->getInstallments();
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
     * @param BoletoTransaction $transaction
     *
     * @return void
     */
    private function saveBoletoInformation($transaction)
    {
        $this->setBoletoExpirationDate(
            $transaction->getBoletoExpirationDate()->getTimestamp()
        );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $infoInstance
     * @param string $referenceKey
     * @param AbstractTransaction $transaction
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function saveTransactionInformation(
        Mage_Sales_Model_Order $order,
                               $infoInstance,
                               $referenceKey,
        AbstractTransaction    $transaction = null
    )
    {
        $this
            ->setReferenceKey($referenceKey)
            ->setOrderId($order->getId());

        if (!is_null($transaction)) {
            $totalAmount = Mage::helper('pagarme_core')
                ->parseAmountToCurrency($transaction->getAmount());

            $this
                ->setTransactionId($transaction->getId())
                ->setPaymentMethod($transaction::PAYMENT_METHOD)
                ->setFutureValue($totalAmount);

            if ($transaction instanceof CreditCardTransaction) {
                $this->saveCreditCardInformation($order, $transaction);
            } else if ($transaction instanceof PixTransaction) {
                $this->setPixQrCode($transaction->getPixQrCode());
                $this->setPixExpirationDate($transaction->getPixExpirationDate());
            } else if ($transaction instanceof BoletoTransaction) {
                $this->saveBoletoInformation($transaction);
            }
        }

        $this->save();
    }
}
