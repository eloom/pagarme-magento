<?php

class PagarMeV5_Creditcard_Model_Installments {
	/**
	 * @var \PagarMeV5\Client
	 */
	private $sdk;

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
	 * @param \PagarMeV5\Client $sdk
	 */
	public function __construct(
		$amount,
		$installments,
		$freeInstallments = 0,
		$interestRate = 0,
		$maxInstallments = 12,
		$sdk = null
	) {
		$this->sdk = $sdk;
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
		return $this->sdk
			->transactions()
			->calculateInstallments(
				[
					'amount' => $this->amount,
					'free_installments' => $this->freeInstallments,
					'max_installments' => $this->maxInstallments,
					'interest_rate' => $this->interestRate
				]
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
		foreach ($installments->installments as $info) {
			if ($installment == $info->installment) {
				return $info->amount;
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
