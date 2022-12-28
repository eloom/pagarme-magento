<?php

class PagarMe_Creditcard_Block_Info extends Mage_Payment_Block_Info_Cc
{
    use PagarMe_Core_Block_Info_Trait;

    /**
     * @var stdClass
     */
    private $transaction;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('pagarme/creditcard/info.phtml');
    }

    /**
     * @return string
     */
    public function transactionInstallments()
    {
        return $this->transaction->installments;
    }

    /**
     * @return string
     */
    public function transactionCustomerName()
    {
        $this->transaction = $this->getTransaction();
        return $this->transaction->customer->name;
    }

    /**
     * @return string
     */
    public function transactionCardHolderName()
    {
        return $this->transaction->card->holder_name;
    }

    /**
     * @return string
     */
    public function transactionCardBrand()
    {
        return $this->transaction->card->brand;
    }

    /**
     * @return int
     */
    public function transactionId()
    {
        return $this->transaction->id;
    }

    /**
     * Render the block only if there's a transaction object
     *
     * @return string
     */
    public function renderView()
    {
        try {
            $this->getTransaction();
        } catch (\Exception $exception) {
        }

        return parent::renderView();
    }
}
