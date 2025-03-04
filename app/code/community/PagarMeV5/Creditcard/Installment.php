<?php

class PagarMeV5_Creditcard_Installment {

	/**
	 *
	 * @var int
	 */
	protected $installment;
	/**
	 *
	 * @var int
	 */
	protected $baseTotal;
	/**
	 *
	 * @var float
	 */
	protected $interest;

	public function __construct($installment, $baseTotal, $interest) {
		$this->setInstallment($installment);
		$this->setBaseTotal($baseTotal);
		$this->setInterest($interest);
	}

	/**
	 *
	 * @param int $installment
	 * @return $this
	 * @throws InvalidParamException
	 */
	private function setInstallment($installment) {
		$newTimes = intval($installment);
		if ($newTimes < 0 || $newTimes > 24) {
			throw new InvalidParamException(
				"A installment times should be set between 0 and 24!",
				$installment
			);
		}
		$this->installment = $newTimes;
		return $this;
	}

	/**
	 *
	 * @param int $baseTotal
	 * @return $this
	 * @throws InvalidParamException
	 */
	private function setBaseTotal($baseTotal) {
		$newBaseTotal = floatval($baseTotal);
		if ($newBaseTotal < 0) {
			throw new InvalidParamException(
				"A installment total price should be greater or equal to 0!",
				$baseTotal
			);
		}
		$this->baseTotal = $newBaseTotal;
		return $this;
	}

	/**
	 *
	 * @param float $interest
	 * @return $this
	 * @throws InvalidParamException
	 */
	private function setInterest($interest) {
		$this->interest = floatval($interest);
		return $this;
	}

	//calculated property getters

	/**
	 *
	 * @return int
	 */
	public function getTotal() {
		$interest = (1 + $this->interest);
		$total = (float)$this->baseTotal * $interest;

		return round($total, 2);
	}

	/**
	 *
	 * @return int
	 */
	public function getValue() {
		$total = (float)$this->getTotal() / $this->installment;

		return round($total, 2);
	}

	//base property getters

	/**
	 *
	 * @return int
	 */
	public function getInstallment() {
		return $this->installment;
	}

	/**
	 *
	 * @return int
	 */
	public function getBaseTotal() {
		return $this->baseTotal;
	}

	/**
	 *
	 * @return float
	 */
	public function getInterest() {
		return $this->interest;
	}
}
