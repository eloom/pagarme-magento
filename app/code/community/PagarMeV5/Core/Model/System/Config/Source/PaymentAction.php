<?php

class PagarMeV5_Core_Model_System_Config_Source_PaymentAction {
	const AUTH_ONLY = 'authorize_only';
	const AUTH_CAPTURE = 'authorize_capture';

	/**
	 * @codeCoverageIgnore
	 *
	 * @return array
	 */
	public function toOptionArray() {
		return [
			[
				'value' => self::AUTH_CAPTURE,
				'label' => Mage::helper('pagarmev5_core')
					->__('Authorize and Capture')
			],
			[
				'value' => self::AUTH_ONLY,
				'label' => Mage::helper('pagarmev5_core')
					->__('Authorize Only')
			]
		];
	}
}
