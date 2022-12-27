<?php

class PagarMe_Pix_Block_Info extends Mage_Payment_Block_Info
{
    use PagarMe_Core_Block_Info_Trait;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('pagarme/pix/order_info/payment_details.phtml');
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
    public function getQrCode()
    {
        return $this->getTransaction()->pix_qr_code;
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
            $this->setTemplate('pagarme/form/payment_method.phtml');
        }

        return parent::renderView();
    }
}
