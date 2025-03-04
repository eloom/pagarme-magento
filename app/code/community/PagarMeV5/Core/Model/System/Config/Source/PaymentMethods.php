<?php

class PagarMeV5_Core_Model_System_Config_Source_PaymentMethods {
	/**
	 * @codeCoverageIgnore
	 *
	 * @return array
	 */
	public function toOptionArray() {
		return [
			[
				'value' => 'pagarmev5_boleto',
				'label' => Mage::helper('pagarmev5_core')->__('Boleto Only')
			],
			[
				'value' => 'pagarmev5_creditcard',
				'label' => Mage::helper('pagarmev5_core')->__('Credit Card Only')
			],
			[
				'value' => 'pagarmev5_creditcard,pagarmev5_boleto',
				'label' => Mage::helper('pagarmev5_core')->__(
					'Boleto and Credit Card'
				)
			]
		];
	}
}
