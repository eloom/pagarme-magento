<?php

use PagarmeCoreApiLib\Models\GetOrderResponse;

class PagarMeV5_Core_Model_Transaction extends Mage_Core_Model_Abstract {

	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	private $logger;

	/**
	 * @return type
	 */
	public function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		return $this->_init('pagarmev5_core/transaction');
	}

	/**
	 * Creates a hash to be used as reference key
	 *
	 * @return string
	 */
	public function getReferenceKey() {
		return md5(uniqid(rand()));
	}

	/**
	 * @param GetOrderResponse $orderResponse
	 *
	 * @return void
	 */
	private function saveCreditCardInformation($orderResponse) {
		$charge = $orderResponse->charges[0];
		$installments = $charge->lastTransaction->installments;
		$interestRate = $this->getInterestRateStoreConfig();

		$this->setInstallments($installments)->setInterestRate($interestRate);
	}

	/**
	 * @param GetOrderResponse $orderResponse
	 *
	 * @return void
	 */
	private function saveBoletoInformation(GetOrderResponse $orderResponse) {
		$this->setBoletoExpirationDate($orderResponse->charges[0]->lastTransaction->dueAt->date);
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @param Mage_Sales_Model_Order_Payment $infoInstance
	 * @param string $referenceKey
	 * @param GetOrderResponse $orderResponse
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function saveTransactionInformation(Mage_Sales_Model_Order $order,
	                                                                  $infoInstance,
	                                                                  $referenceKey,
	                                           GetOrderResponse       $orderResponse = null
	) {
		$this->setReferenceKey($referenceKey)->setOrderId($order->getId());

		if (!is_null($orderResponse)) {
			$totalAmount = Mage::helper('pagarmev5_core')->parseAmountToCurrency($orderResponse->amount);

			$this->setTransactionId($orderResponse->id)->setPaymentMethod($orderResponse->payment_method)->setFutureValue($totalAmount);

			$paymentMethod = $orderResponse->charges[0]->paymentMethod;

			if ($paymentMethod == 'credit_card') {
				$this->saveCreditCardInformation($orderResponse);
			} else if ($paymentMethod == 'pix') {
				//$this->setPixQrCode($orderResponse->pix_qr_code);
				//$this->setPixExpirationDate($orderResponse);
			} else if ($paymentMethod == 'boleto') {
				$this->saveBoletoInformation($orderResponse);
			}

			if ($order->getCanSendNewEmailFlag() && !$order->getEmailSent()) {
				try {
					$order->sendNewOrderEmail();
				} catch (Exception $e) {
					$this->logger->fatal($e->getTraceAsString());
				}
			}
		}

		$this->save();
	}
}
