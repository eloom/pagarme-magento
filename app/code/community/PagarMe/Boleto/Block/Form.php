<?php
class PagarMe_Boleto_Block_Form extends Mage_Payment_Block_Form
{
    use PagarMe_Core_Trait_ConfigurationsAccessor;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pagarme/form/boleto.phtml');
    }

    public function getEncryptionKey()
    {
        return Mage::getStoreConfig(
            'payment/pagarme/encryption_key'
        );
    }

    public function getCheckoutConfig()
    {
        $quote = $this->getQuote();
    }

    public function getCurrentSubtotal()
    {
        $subtotalPunctuated = Mage::getModel('checkout/session')->getQuote()->getData()['subtotal'];
        return preg_replace('/[^0-9]/', '', $subtotalPunctuated);
    }

    public function getAdminBoletoInstructions()
    {
      return $this->getBoletoInstructions();
    }
}
