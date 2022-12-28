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
     * @return stdClass
     */
    public function getTransactionById($transactionId)
    {
        return $this
            ->getSdk()
            ->transactions()
            ->get(['id' => $transactionId]);
    }

    /**
     * @param stdClass $transaction
     *
     * @return stdClass
     *
     * @throws Exception
     */
    public function capture(stdClass $transaction)
    {
        try {
            return $this->getSdk()
                ->transactions()
                ->capture(
                    [
                        'id' => $transaction,
                        'amount' => $transaction->amount
                    ]
                );
        } catch (Exception $exception) {
            throw $exception;
        }
    }
}
