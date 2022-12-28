<?php

class PagarMe_Pix_Block_Form extends Mage_Payment_Block_Form
{
    use PagarMe_Core_Trait_ConfigurationsAccessor;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('pagarme/pix/form.phtml');
    }

    public function getInstructions()
    {
        return $this->getPixInstructions();
    }
}
