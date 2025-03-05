<?php

use PagarMeV5_Core_Model_CurrentOrder as CurrentOrder;

class PagarMeV5_Creditcard_Model_Installments {

	/**
	 * @var integer
	 */
	private $amount;

	/**
	 * @var float
	 */
	private $interestRate;

	/**
	 * @var integer
	 */
	private $freeInstallments;

	/**
	 * @var integer
	 */
	private $maxInstallments;

	/**
	 * @param int $amount
	 * @param int $installments
	 * @param int $freeInstallments
	 * @param float $interestRate
	 * @param int $maxInstallments
	 */
	public function __construct(
		$amount,
		$installments,
		$freeInstallments = 0,
		$interestRate = 0,
		$maxInstallments = 12
	) {
		$this->amount = $amount;
		$this->installments = $installments;
		$this->freeInstallments = $freeInstallments;
		$this->interestRate = $interestRate;
		$this->maxInstallments = $maxInstallments;
	}

	/**
	 * @return array
	 */
	private function calculate() {
		$quote = Mage::helper('checkout')->getQuote();
		$currentOrder = new CurrentOrder($quote);

		return $currentOrder->calculateInstallments($this->maxInstallments,
			$this->freeInstallments,
			$this->interestRate
		);
	}

	/**
	 * @return int
	 */
	public function getTotal() {
		return $this->getInstallmentTotalAmount($this->installments);
	}

	/**
	 * @param int $installment
	 *
	 * @return int
	 */
	public function getInstallmentTotalAmount($installment) {
		$installments = $this->calculate();
		foreach ($installments as $info) {
			if ($installment == $info->getInstallment()) {
				return $info->getTotal();
			}
		}
	}

	/**
	 * @return int
	 */
	public function getRateAmount() {
		return intval($this->getTotal() - $this->amount);
	}
}
