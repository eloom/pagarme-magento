<?php

class PagarMe_Core_Model_System_Config_Source_BoletoDiscountMode {
	const NO_DISCOUNT = 'no_discount';
	const FIXED_VALUE = 'fixed_value';
	const PERCENTAGE = 'percentage';

	/**
	 * @codeCoverageIgnore
	 *
	 * @return array
	 */
	public function toOptionArray() {
		$helper = Mage::helper('pagarme_boleto');

		return [
			self::NO_DISCOUNT => $helper->__('No discount'),
			self::FIXED_VALUE => $helper->__('Fixed value'),
			self::PERCENTAGE => $helper->__('Percentage')
		];
	}
}
