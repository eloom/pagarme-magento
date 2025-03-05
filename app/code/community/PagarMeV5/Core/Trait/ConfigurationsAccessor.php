<?php

trait PagarMeV5_Core_Trait_ConfigurationsAccessor {
	/**
	 * Returns true only if magento is running with developer mode enabled
	 *
	 * @return bool
	 */
	public function isDeveloperModeEnabled() {
		if (Mage::getIsDeveloperMode() ||
			getenv('PAGARME_DEVELOPMENT') === 'enabled'
		) {
			return true;
		}

		return false;
	}

	/**
	 * Returns postback url defined on Pagar.me's settings panel
	 *
	 * @return string
	 */
	private function getDevelopmentPostbackUrl() {
		$devPostbackUrl = trim(Mage::getStoreConfig('payment/pagarmev5/dev_custom_postback_url'));

		if (!filter_var($devPostbackUrl, FILTER_VALIDATE_URL)) {
			return '';
		}

		if (substr($devPostbackUrl, 1, 1) !== '/') {
			$devPostbackUrl .= '/';
		}

		return $devPostbackUrl;
	}

	/**
	 * @return int
	 */
	private function getMaxInstallmentStoreConfig() {
		return (int)Mage::getStoreConfig('payment/pagarmev5_creditcard/max_installments');
	}

	/**
	 * @return float
	 */
	private function getMinInstallmentValueStoreConfig() {
		return (float)Mage::getStoreConfig('payment/pagarmev5_creditcard/min_installment_value');
	}

	/**
	 * @return string
	 */
	public function getPublicKey() {
		return trim(Mage::getStoreConfig('payment/pagarmev5/public_key'));
	}

	/**
	 * @return string
	 */
	public function getPaymentActionConfig() {
		return Mage::getStoreConfig('payment/pagarmev5_creditcard/payment_action');
	}

	/**
	 * @return int
	 */
	private function getFreeInstallmentStoreConfig() {
		return (int)Mage::getStoreConfig('payment/pagarmev5_creditcard/free_installments');
	}

	/**
	 * @return float
	 */
	private function getInterestRateStoreConfig() {
		return (float)Mage::getStoreConfig('payment/pagarmev5_creditcard/interest_rate');
	}

	/**
	 * @return int
	 */
	private function getDaysToBoletoExpire() {
		return (int)Mage::getStoreConfig('payment/pagarmev5_boleto/days_to_expire');
	}

	/**
	 * @return string
	 */
	private function getBoletoInstructions() {
		return Mage::getStoreConfig('payment/pagarmev5_boleto/instructions');
	}

	private function getPixInstructions() {
		return Mage::getStoreConfig('payment/pagarmev5_pix/instructions');
	}
}
