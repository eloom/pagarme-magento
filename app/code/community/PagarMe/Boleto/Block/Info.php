<?php

class PagarMe_Boleto_Block_Info extends Mage_Payment_Block_Info
{
    use PagarMe_Core_Block_Info_Trait;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('pagarme/boleto/info.phtml');
    }

    /**
     * @return int
     * @throws Exception
     */
    public function transactionId()
    {
        return $this->getTransaction()->id;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getBoletoUrl()
    {
        return $this->getTransaction()->boleto_url;
    }

    /**
     * Returns the template to be showed
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
