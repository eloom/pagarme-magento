<?php

use PagarMeV5_Core_Model_CurrentOrder as CurrentOrder;

class PagarMeV5_Creditcard_Block_Form extends Mage_Payment_Block_Form_Cc {

	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	protected function _construct() {
		parent::_construct();
		$this->setTemplate('pagarme-v5/creditcard/form.phtml');
	}

	/**
	 * @return array
	 */
	public function getInstallments() {
		$quote = Mage::helper('checkout')->getQuote();
		$currentOrder = new CurrentOrder($quote);

		$maxInstallments = $this->getMaxInstallmentsByMinimumAmount(
			$currentOrder->productsTotalValueInBRL()
		);

		return $currentOrder->calculateInstallments($maxInstallments,
			$this->getFreeInstallmentStoreConfig(),
			$this->getInterestRateStoreConfig()
		);
	}

	/**
	 * @param float $orderTotal
	 *
	 * @return int
	 */
	public function getMaxInstallmentsByMinimumAmount($orderTotal) {
		$minInstallmentAmount = $this->getMinInstallmentValueStoreConfig();

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
