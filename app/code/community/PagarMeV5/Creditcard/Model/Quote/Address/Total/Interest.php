<?php

class PagarMeV5_Creditcard_Model_Quote_Address_Total_Interest extends Mage_Sales_Model_Quote_Address_Total_Abstract {

	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	protected $_code = 'pagarmev5_interest';

	public function collect(Mage_Sales_Model_Quote_Address $address) {
		parent::collect($address);

		$this->_setAmount(0);
		$this->_setBaseAmount(0);
		$address->setPagarmev5InterestAmount(0);
		$address->setPagarmev5BaseInterestAmount(0);

		$items = $this->_getAddressItems($address);
		if (!count($items)) {
			return $this;
		}

		$interest = Mage::getSingleton('pagarmev5_creditcard/interest');
		if ($interest->canApply($address)) {
			$paymentInterest = $interest->getInterest();
			$store = $address->getQuote()->getStore();

			$shippingAmount = $address->getShippingAmount();
			$amount = ($paymentInterest->baseSubtotalWithDiscount + $paymentInterest->baseTax + $shippingAmount);

			$installmentValue = Mage::helper('pagarmev5_creditcard/math')->calculatePayment($amount, $paymentInterest->getTotalPercent() / 100, $paymentInterest->getInstallment());
			$baseTotalInterestAmount = ($installmentValue * $paymentInterest->getInstallment()) - $amount;
			$baseTotalInterestAmount = $store->roundPrice($baseTotalInterestAmount);

			$totalInterestAmount = Mage::helper('directory')->currencyConvert($baseTotalInterestAmount, $paymentInterest->baseCurrencyCode);

			$address->setPagarmev5InterestAmount($totalInterestAmount);
			$address->setPagarmev5BaseInterestAmount($baseTotalInterestAmount);

			$address->setGrandTotal($address->getGrandTotal() + $address->getPagarmev5InterestAmount());
			$address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getPagarmev5BaseInterestAmount());
		}

		return $this;
	}

	public function fetch(Mage_Sales_Model_Quote_Address $address) {
		$amount = $address->getPagarmev5InterestAmount();
		if ($amount != 0) {
			$address->addTotal(array('code' => $this->getCode(),
				'title' => Mage::helper('pagarmev5_creditcard')->__('Interest'),
				'value' => $amount
			));
		}
		return $this;
	}
}
