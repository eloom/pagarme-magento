<?php

class PagarMeV5_Boleto_Block_Form extends Mage_Payment_Block_Form {
	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	protected function _construct() {
		parent::_construct();
		$this->setTemplate('pagarme-v5/boleto/form.phtml');
	}

	public function getInstructions() {
		return $this->getBoletoInstructions();
	}
}
