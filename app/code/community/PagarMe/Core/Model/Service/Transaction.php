<?php

class PagarMe_Core_Model_Service_Transaction
{
    /**
     * @var \PagarMe\Client
     */
    protected $sdk;
    
    /**
     * @return \PagarMe\Client
     */
    public function getSdk()
    {
        if (is_null($this->sdk)) {
            $this->setSdk(
                Mage::getModel('pagarme_core/sdk_adapter')
                    ->getSdk()
            );
        }

        return $this->sdk;
    }

    /**
     * @param \PagarMe\Client $sdk
     *
     * @return void
     */
    public function setSdk(\PagarMe\Client $sdk)
    {
        $this->sdk = $sdk;
    }

    /**
     * @param int $transactionId
     * @return \PagarMe\Sdk\Transaction\AbstractTransaction
     */
    public function getTransactionById($transactionId)
    {
        return $this
            ->getSdk()
            ->transaction()
            ->get($transactionId);
    }

    /**
     * @param \PagarMe\Sdk\Transaction\AbstractTransaction $transaction
     *
     * @return \PagarMe\Sdk\Transaction\AbstractTransaction
     *
     * @throws Exception
     */
    public function capture(
        \PagarMe\Sdk\Transaction\AbstractTransaction $transaction
    ) {
        try {
            return $this->getSdk()
                ->transaction()
                ->capture(
                    $transaction,
                    $transaction->getAmount()
                );
        } catch (Exception $exception) {
            throw $exception;
        }
    }
}
