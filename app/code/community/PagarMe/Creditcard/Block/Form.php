<?php

use PagarMe_Core_Model_CurrentOrder as CurrentOrder;

class PagarMe_Creditcard_Block_Form extends Mage_Payment_Block_Form_Cc {
	use PagarMe_Core_Trait_ConfigurationsAccessor;

	protected function _construct() {
		parent::_construct();
		$this->setTemplate('pagarme/creditcard/form.phtml');
	}

	/**
	 * @return array
	 */
	public function getInstallments() {
		$quote = Mage::helper('checkout')->getQuote();
		$sdk = Mage::getModel('pagarme_core/sdk_adapter');
		$currentOrder = new CurrentOrder(
			$quote,
			$sdk
		);

		$maxInstallments = $this->getMaxInstallmentsByMinimumAmount(
			$currentOrder->productsTotalValueInBRL()
		);

		$calculateInstallments = $currentOrder->calculateInstallments(
			$maxInstallments,
			$this->getFreeInstallmentStoreConfig(),
			$this->getInterestRateStoreConfig()
		);

		return $calculateInstallments->installments;
	}

	/**
	 * @param float $orderTotal
	 *
	 * @return int
	 */
	public function getMaxInstallmentsByMinimumAmount($orderTotal) {
		$minInstallmentAmount = $this->getMinInstallmentValueStoreConfig();

		//$maxInstallmentsConfig = $this->getMaxInstallmentStoreConfig();

		if ($minInstallmentAmount <= 0) {
			return $this->getMaxInstallmentStoreConfig();
		}

		$installmentsNumber = floor($orderTotal / $minInstallmentAmount);

		$maxInstallments = $installmentsNumber ? $installmentsNumber : 1;

		if ($maxInstallments > $this->getMaxInstallmentStoreConfig()) {
			return $this->getMaxInstallmentStoreConfig();
		}

		return $maxInstallments;
	}

	/**
	 * @return int
	 */
	public function getFreeInstallmentsConfig() {
		return $this->getFreeInstallmentStoreConfig();
	}
}
