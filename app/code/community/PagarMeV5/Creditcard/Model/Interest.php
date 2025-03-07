<?php

##eloom.licenca##

class PagarMeV5_Creditcard_Model_Interest extends Mage_Core_Model_Abstract {

	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	const DATA_CC_INSTALLMENTS = 'cc-installments';

	const CODE = 'pagarmev5_interest';

	public function canApply($address): bool {
		$data = Mage::app()->getRequest()->getPost('payment', array());
		if (!count($data) || !isset($data[self::DATA_CC_INSTALLMENTS])) {
			return false;
		}

		$currentPaymentMethod = null;

		$sessionQuote = Mage::getSingleton('checkout/session')->getQuote();
		if ($sessionQuote->getPayment() != null && $sessionQuote->getPayment()->hasMethodInstance()) {
			$currentPaymentMethod = $sessionQuote->getPayment()->getMethodInstance()->getCode();
		} elseif (isset($data['method'])) {
			$currentPaymentMethod = $data['method'];
		}

		if ($currentPaymentMethod == 'pagarmev5_creditcard') {
			$arrayex = explode('-', $data[self::DATA_CC_INSTALLMENTS]);
			$installments = $arrayex[0];

			if ($installments != null && $installments > $this->getFreeInstallmentStoreConfig()) {
				return true;
			}
		}

		return false;
	}

	public function getInterest() {
		$data = Mage::app()->getRequest()->getPost('payment', array());
		$installments = 1;
		$arrayex = explode('-', $data[self::DATA_CC_INSTALLMENTS]);
		if (isset($arrayex[0])) {
			$installments = $arrayex[0];
		}
		$interest = str_replace(',', '.', $this->getInterestRateStoreConfig());

		$baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
		$baseSubtotalWithDiscount = 0;
		$baseTax = 0;

		$quote = Mage::getSingleton('checkout/session')->getQuote();
		if ($quote->isVirtual()) {
			$address = $quote->getBillingAddress();
		} else {
			$address = $quote->getShippingAddress();
		}
		if ($address) {
			$baseSubtotalWithDiscount = $address->getBaseSubtotalWithDiscount();
			$baseTax = $address->getBaseTaxAmount();
		}

		return PagarMeV5_Creditcard_Interest::getInstance($baseCurrencyCode, $interest, $baseSubtotalWithDiscount, $baseTax, $installments);
	}

	public function getModuleInterest($order) {
		return $order->getPagarmev5InterestAmount();
	}

	public function getModuleBaseInterest($order) {
		return $order->getPagarmev5BaseInterestAmount();
	}

	public function getModuleInterestCode(): string {
		return self::CODE;
	}
}
