<?php

##eloom.licenca##

class PagarMeV5_Creditcard_Interest extends stdClass {

  /**
   *
   * @var type 
   */
  public $baseCurrencyCode;

  /**
   *
   * @var type 
   */
  public $totalPercent;

  /**
   *
   * @var type
   */
  public $baseSubtotalWithDiscount;

  /**
   *
   * @var type
   */
  public $baseTax;

  public $installment;

  public function __construct() {
    $this->baseCurrencyCode = 0;
    $this->totalPercent = 0;
    $this->baseSubtotalWithDiscount = 0;
    $this->baseTax = 0;
    $this->installment = 0;
  }

  public static function getInstance($baseCurrencyCode, $totalPercent, $baseSubtotalWithDiscount, $baseTax, $installment) {
    $paymentInterest = new PagarMeV5_Creditcard_Interest();
    $paymentInterest->setBaseCurrencyCode($baseCurrencyCode);
    $paymentInterest->setTotalPercent($totalPercent);
    $paymentInterest->setBaseSubtotalWithDiscount($baseSubtotalWithDiscount);
    $paymentInterest->setBaseTax($baseTax);
		$paymentInterest->setInstallment($installment);

    return $paymentInterest;
  }

  public function getTotalPercent() {
    return $this->totalPercent;
  }

  public function setTotalPercent($totalPercent) {
    $this->totalPercent = $totalPercent;
  }

  public function getBaseCurrencyCode() {
    return $this->baseCurrencyCode;
  }

  public function setBaseCurrencyCode($baseCurrencyCode) {
    $this->baseCurrencyCode = $baseCurrencyCode;
  }

  public function getBaseSubtotalWithDiscount() {
    return $this->baseSubtotalWithDiscount;
  }

  public function setBaseSubtotalWithDiscount($baseSubtotalWithDiscount) {
    $this->baseSubtotalWithDiscount = $baseSubtotalWithDiscount;
  }

  public function getBaseTax() {
    return $this->baseTax;
  }

  public function setBaseTax($baseTax) {
    $this->baseTax = $baseTax;
  }

	/**
	 * @return int
	 */
	public function getInstallment() {
		return $this->installment;
	}

	/**
	 * @param int $installment
	 */
	public function setInstallment($installment) {
		$this->installment = $installment;
	}
}
