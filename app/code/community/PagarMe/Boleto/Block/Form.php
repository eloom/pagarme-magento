<?php
class PagarMe_Boleto_Block_Form extends Mage_Payment_Block_Form
{
    use PagarMe_Core_Trait_ConfigurationsAccessor;

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('pagarme/boleto/form.phtml');
    }

    public function getInstructions() {
      return $this->getBoletoInstructions();
    }
}
