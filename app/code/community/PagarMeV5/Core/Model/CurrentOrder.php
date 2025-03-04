<?php

use PagarMeV5_Creditcard_Installment as Installment;

class PagarMeV5_Core_Model_CurrentOrder {

	/**
	 * @var \Mage_Sales_Model_Quote
	 */
	private $quote;

	public function __construct(Mage_Sales_Model_Quote $quote) {
		$this->quote = $quote;
	}

	public function calculateInstallments(
		$maxInstallments,
		$freeInstallments,
		$interestRate
	) {
		$amount = $this->orderGrandTotalInCents();

		$installments = [];
		for ($i = 1; $i <= $freeInstallments; $i++) {
			$installment = new Installment($i, $amount, 0);
			$installments[] = $installment;
		}

		for ($i = $freeInstallments + 1, $interestCicle = 0; $i <= $maxInstallments; $i++, $interestCicle++) {
			$interest = $interestRate;
			$interest += $interestCicle;
			$installments[] = new Installment($i, $amount, $interest / 100);
		}

		return $installments;
	}

	/**
	 * @return int
	 * @see self::productsTotalValueInCents
	 *
	 * @deprecated
	 */
	public function productsTotalValueInCents() {
		return $this->orderGrandTotalInCents();
	}

	/**
	 * GrandTotal represents the value of the shipping + cart items total
	 * considering the discount amount
	 *
	 * @return int
	 */
	public function orderGrandTotalInCents() {
		$total = $this->quote->getData()['grand_total'];

		return Mage::helper('pagarmev5_core')->parseAmountToCents($total);
	}

	public function productsTotalValueInBRL() {
		$total = $this->productsTotalValueInCents();
		return Mage::helper('pagarmev5_core')->parseAmountToCurrency($total);
	}

	/**
	 * May result in slowing the payment method view in the checkout
	 *
	 * @param int $installmentsValue
	 * @param int $freeInstallments
	 * @param float $interestRate
	 *
	 * @return float
	 */
	public function rateAmountInBRL(
		$installmentsValue,
		$freeInstallments,
		$interestRate
	) {
		$installments = $this->calculateInstallments(
			$installmentsValue,
			$freeInstallments,
			$interestRate
		);

		$installmentTotal = $installments[$installmentsValue]['total_amount'];
		return Mage::helper('pagarmev5_core')->parseAmountToCurrency(
			$installmentTotal - $this->productsTotalValueInCents()
		);
	}
}
