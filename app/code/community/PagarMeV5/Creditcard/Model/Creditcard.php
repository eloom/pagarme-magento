<?php

use PagarMeV5_Core_Model_System_Config_Source_PaymentAction as PaymentActionConfig;
use PagarMeV5_Creditcard_Model_Exception_InvalidInstallments as InvalidInstallmentsException;
use PagarmeCoreApiLib\Models\CreateAddressRequest;
use PagarmeCoreApiLib\Models\CreateCreditCardPaymentRequest;
use PagarmeCoreApiLib\Models\CreateOrderItemRequest;
use PagarmeCoreApiLib\Models\CreateOrderRequest;
use PagarmeCoreApiLib\Models\CreatePaymentRequest;
use PagarmeCoreApiLib\Models\CreateShippingRequest;
use PagarmeCoreApiLib\Models\GetOrderResponse;
use PagarmeCoreApiLib\Models\CreateCardRequest;
use PagarmeCoreApiLib\PagarmeCoreApiClient;

class PagarMeV5_Creditcard_Model_Creditcard extends PagarMeV5_Core_Model_AbstractPaymentMethod {

	private $logger;

	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	const CC = 'pagarmev5_creditcard';

	/**
	 * @var string
	 */
	protected $_code = self::CC;

	/**
	 * @var string
	 */
	protected $_formBlockType = 'pagarmev5_creditcard/form';

	/**
	 * @var string
	 */
	protected $_infoBlockType = 'pagarmev5_creditcard/info';

	/**
	 * @var boolean
	 */
	protected $_isGateway = true;

	/**
	 * @var boolean
	 */
	protected $_canAuthorize = true;

	/**
	 * @var boolean
	 */
	protected $_canCapture = true;

	/**
	 * @var boolean
	 */
	protected $_canCapturePartial = true;

	/**
	 * @var boolean
	 */
	protected $_canRefund = true;

	/**
	 * @var boolean
	 */
	protected $_canRefundInvoicePartial = true;

	/**
	 * @var boolean
	 */
	protected $_canUseForMultishipping = true;

	/**
	 * @var boolean
	 */
	protected $_canManageRecurringProfiles = true;

	/**
	 * @var boolean
	 */
	protected $_isInitializeNeeded = true;

	/**
	 * @var PagarmeCoreApiClient
	 */
	protected $sdk;

	/**
	 * @var GetOrderResponse
	 */
	protected $getOrderResponse;

	/**
	 * @var PagarMeV5_Core_Helper_Data
	 */
	protected $pagarmeCoreHelper;

	/**
	 * @var PagarMeV5_Creditcard_Helper_Data
	 */
	protected $pagarmeCreditCardHelper;

	/**
	 * @var Mage_Sales_Model_Quote
	 */
	protected $quote;

	/**
	 * @var PagarMeV5_Core_Model_Transaction
	 */
	protected $transactionModel;

	/**
	 * @var \Varien_Object
	 */
	private $stateObject;

	const PAGARME_MAX_INSTALLMENTS = 12;
	const POSTBACK_ENDPOINT = 'transaction_notification';

	const AUTHORIZED = 'authorized';
	const PAID = 'paid';

	public function __construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);

		$this->sdk = Mage::getModel('pagarmev5_core/sdk_adapter')->getSdk();
		$this->pagarmeCoreHelper = Mage::helper('pagarmev5_core');
		$this->pagarmeCreditCardHelper = Mage::helper('pagarmev5_creditcard');
		$this->transactionModel = Mage::getModel('pagarmev5_core/transaction');
	}

	/**
	 * Method that will be executed instead of magento's default workflow
	 * (authorize or capture)
	 *
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 *
	 * @return Mage_Payment_Model_Method_Abstract
	 */
	public function initialize($paymentAction, $stateObject) {
		$this->stateObject = $stateObject;

		$paymentActionConfig = $this->getPaymentActionConfig();
		$asyncTransactionConfig = (bool)$this->getAsyncTransactionConfig();
		$payment = $this->getInfoInstance();

		$this->stateObject->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
		$this->stateObject->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
		$this->stateObject->setIsNotified(true);

		if ($paymentActionConfig === PaymentActionConfig::AUTH_ONLY ||
			$asyncTransactionConfig === true
		) {
			$stateObject->setState(
				Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
			);
			$stateObject->setStatus(
				Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
			);
		}

		$this->authorize($payment, $payment->getOrder()->getGrandTotal());
		$payment->setAmountAuthorized($payment->getOrder()->getTotalDue());

		return $this;
	}

	/**
	 * @param GetOrderResponse $getOrderResponse
	 *
	 * @return void
	 */
	public function setGetOrderResponse(GetOrderResponse $getOrderResponse) {
		$this->getOrderResponse = $getOrderResponse;
	}

	/**
	 * @return string
	 */
	protected function getPostbackCode() {
		return self::POSTBACK_ENDPOINT;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function assignData($data) {
		$additionalInfoData = [
			'card_hash' => $data['cc-hash'],
			'card_number' => $data['cc-number'],
			'card_holder_name' => $data['cc-holder-name'],
			'card_exp_month' => trim(explode("/", $data['cc-exp-date'])[0]),
			'card_exp_year' => trim(explode("/", $data['cc-exp-date'])[1]),
			'card_cvv' => $data['cc-cvv'],
			'card_installments' => $data['cc-installments']
		];
		$info = $this->getInfoInstance();
		$info->setAdditionalInformation($additionalInfoData);

		return $this;
	}

	public function getMaxInstallment() {
		return $this->getMaxInstallmentStoreConfig();
	}

	/**
	 * Check if installments is between 1 and the defined max installments
	 *
	 * @param int $installments
	 *
	 * @return void
	 * @throws InvalidInstallmentsException
	 *
	 */
	public function isInstallmentsValid($installments) {
		if ($installments <= 0) {
			$message = $this->pagarmeCoreHelper->__(
				'Please, select the number of installments.'
			);
			throw new InvalidInstallmentsException($message);
		}

		if ($installments > self::PAGARME_MAX_INSTALLMENTS) {
			$message = $this->pagarmeCreditCardHelper->__(
				'Installments number should be lower than Pagar.Me limit'
			);
			throw new InvalidInstallmentsException($message);
		}

		if ($installments > $this->getMaxInstallment()) {
			$message = sprintf(
				Mage::helper('pagarmev5_creditcard')
					->__('Installments number should not be greater than %d'),
				$this->getMaxInstallment()
			);
			$message = $this->pagarmeCoreHelper->__($message);
			throw new InvalidInstallmentsException($message);
		}
	}

	/**
	 * @param string $cardHash
	 *
	 * @return PagarmeCard
	 * @throws GenerateCardException
	 */
	public function generateCard($cardHash) {
		try {
			$card = $this->sdk->cards()->get(['id' => $cardHash]);
			return $card;
		} catch (\Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
		}
	}

	/**
	 * Return if a given transaction was paid
	 *
	 * @return bool
	 */
	public function transactionIsPaid() {
		if (is_null($this->getOrderResponse)) {
			return false;
		}

		if ($this->getOrderResponse->status == self::PAID) {
			return true;
		}

		return false;
	}

	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return void
	 */
	protected function createInvoice($order) {
		$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

		$invoice->setBaseGrandTotal($order->getGrandTotal());
		$invoice->setGrandTotal($order->getGrandTotal());
		$invoice->setInterestAmount($order->getInterestAmount());
		$invoice->register()->pay();
		$invoice->setTransactionId($this->getOrderResponse->id);

		Mage::getModel('core/resource_transaction')->addObject($order)->addObject($invoice)->save();
	}

	/**
	 * @return string
	 */
	public function getReferenceKey() {
		return $this->transactionModel->getReferenceKey();
	}

	/**
	 * Add to payment card informations provided from API
	 *
	 * @param \Mage_Sales_Model_Order_Payment $payment
	 * @param stdClass $card
	 *
	 * @return \Mage_Sales_Model_Order_Payment
	 */
	public function insertCardInfosOnPayment($payment, $card) {
		$payment
			->setCcType($card->brand)
			->setCcOwner($card->holder_name)
			->setCcLast4($card->last_digits);

		return $payment;
	}

	/**
	 * @return string
	 */
	private function buildCheckoutRefusedMessage() {
		$defaultMessage = $this->pagarmeCreditCardHelper
			->__('Payment refused.');
		$contactMessage = $this->pagarmeCreditCardHelper
			->__('Please, contact your bank for more informations.');

		if ($this->getOrderResponse->refuse_reason === 'antifraud') {
			$contactMessage = $this->pagarmeCreditCardHelper
				->__('Please, contact us for more informations.');
		}

		return sprintf(
			"%s\n%s",
			$defaultMessage,
			$contactMessage
		);
	}

	/**
	 * @param \Mage_Sales_Model_Order_Payment $payment
	 *
	 * @return \Varien_Object
	 * @throws Mage_Payment_Model_Info_Exception
	 */
	private function handlePaymentStatus(Mage_Sales_Model_Order_Payment $payment) {
		$order = $payment->getOrder();
		$notifyCustomer = false;
		$amount = Mage::helper('core')->currency(
			$order->getGrandTotal(),
			true,
			false
		);

		switch ($this->getOrderResponse->status) {
			case 'processing':
				$message = 'Processing on Gateway. Waiting response';
				$desiredStatus = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				break;
			case 'refused':
				throw new Mage_Payment_Model_Info_Exception(
					$this->buildCheckoutRefusedMessage()
				);
				break;
			case 'pending_review':
				$message = 'Waiting transaction review on Pagar.me Dashboard';
				$desiredStatus = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
				break;
			case 'analyzing':
				$message = 'Transaction waiting for antifraud analysis';
				$desiredStatus = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
				break;
			case 'authorized':
				$message = 'Authorized amount of %s';
				$desiredStatus = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
				$notifyCustomer = true;
				break;
			case 'paid':
				$message = 'Captured amount of %s';
				$desiredStatus = Mage_Sales_Model_Order::STATE_PROCESSING;
				$notifyCustomer = true;
				break;
		}

		$order->setState(
			$desiredStatus,
			$desiredStatus,
			$this->pagarmeCoreHelper->__($message, $amount),
			$notifyCustomer
		);

		return $payment;
	}

	/**
	 * Defines additional information from transaction
	 *
	 * @param Mage_Sales_Model_Order_Payment $infoInstance $infoInstance
	 * @param stdClass $transaction
	 *
	 * @return array
	 */
	private function getPaymentAdditionalInformation(
		$infoInstance,
		$transaction
	) {
		return array_merge(
			$infoInstance->getAdditionalInformation(),
			[
				'pagarme_transaction_id' => $transaction->id,
			]
		);
	}

	/**
	 * Authorize payment abstract method
	 *
	 * @param Mage_Sales_Model_Order_Payment $payment
	 * @param float $amount
	 *
	 * @return Mage_Payment_Model_Abstract
	 */
	public function authorize(Mage_Sales_Model_Order_Payment $payment, $amount) {
		$paymentActionConfig = $this->getPaymentActionConfig();
		/*
		$captureTransaction = 'auth_and_capture';
		if ($paymentActionConfig === PaymentActionConfig::AUTH_ONLY) {
				$captureTransaction = 'auth_only';
		}
		*/
		$infoInstance = $this->getInfoInstance();
		$order = $payment->getOrder();
		$order->setCapture($paymentActionConfig);
		$referenceKey = $this->getReferenceKey();
		$installments = (int)$infoInstance->getAdditionalInformation('card_installments');

		$billingAddress = $order->getBillingAddress();
		$shippingAddress = null;
		if ($order->getIsVirtual()) {
			$shippingAddress = $order->getBillingAddress();
		} else {
			$shippingAddress = $order->getShippingAddress();
		}

		try {
			//$helper = Mage::helper('eloombootstrap');

			$this->isInstallmentsValid($installments);
			//$card = $this->generateCard($cardHash); // FIXME: rever

			$telephone = preg_replace("/[^0-9]/", "", $billingAddress->getTelephone());
			$customerName = $this->pagarmeCoreHelper->getCustomerNameFromQuote($order);
			$customerPhoneDdd = $this->pagarmeCoreHelper->getDddFromPhoneNumber($telephone);
			$customerPhoneNumber = $this->pagarmeCoreHelper->getPhoneWithoutDdd($telephone);

			$customer = $this->pagarmeCoreHelper->prepareCustomerData([
				'customer_id' => $order->getCustomerEmail(),
				'customer_type' => $this->pagarmeCoreHelper->getCustomerType($order->getCustomerTaxvat()),
				'customer_document_number' => $order->getCustomerTaxvat(),
				'customer_document_type' => $this->pagarmeCoreHelper->getDocumentType($order->getCustomerTaxvat()),
				'customer_name' => $customerName,
				'customer_email' => $order->getCustomerEmail(),
				'customer_phone_ddd' => $customerPhoneDdd,
				'customer_phone_number' => $customerPhoneNumber,
				'customer_address' => $billingAddress
			]);

			$amount = $this->pagarmeCoreHelper->parseAmountToCents($amount);

			$items = $this->pagarmeCoreHelper->prepareOrderItems($order);

			$billingAddressRequest = $this->pagarmeCoreHelper->prepareAddressData($billingAddress);
			$cardRequest = new CreateCardRequest();
			$cardRequest->number = $infoInstance->getAdditionalInformation('card_number');
			$cardRequest->holderName = $infoInstance->getAdditionalInformation('card_holder_name');
			$cardRequest->expMonth = $infoInstance->getAdditionalInformation('card_exp_month');
			$cardRequest->expYear = $infoInstance->getAdditionalInformation('card_exp_year');
			$cardRequest->cvv = $infoInstance->getAdditionalInformation('card_cvv');
			$cardRequest->billingAddress = $billingAddressRequest;

			$creditCardPayment = new CreateCreditCardPaymentRequest();
			$creditCardPayment->operationType = 'auth_and_capture';
			$creditCardPayment->installments = $installments;
			$creditCardPayment->card = $cardRequest;
			//$creditCardPayment->cardToken = $infoInstance->getAdditionalInformation('card_hash');

			$paymentRequest = new CreatePaymentRequest();
			$paymentRequest->paymentMethod = 'credit_card';
			$paymentRequest->amount = $amount;
			$paymentRequest->creditCard = $creditCardPayment;

			$shippingAddressRequest = $this->pagarmeCoreHelper->prepareAddressData($shippingAddress);
			$shippingRequest = new CreateShippingRequest();
			$shippingRequest->amount = $this->pagarmeCoreHelper->parseAmountToCents($order->getShippingAmount());
			$shippingRequest->description = $order->getShippingDescription();
			$shippingRequest->recipientName = $customerName;
			$shippingRequest->recipientPhone = $customerPhoneDdd . $customerPhoneNumber;
			$shippingRequest->address = $shippingAddressRequest;

			$orderRequest = new CreateOrderRequest();
			$orderRequest->code = $order->getIncrementId();
			$orderRequest->closed = true;
			$orderRequest->ip = $order->getRemoteIp();
			$orderRequest->items = $items;
			$orderRequest->customer = $customer;
			$orderRequest->shipping = $shippingRequest;
			$orderRequest->payments = [$paymentRequest];
			$orderRequest->metadata = $this->pagarmeCoreHelper->prepareMetadata($order, $referenceKey);

			$this->logger->info($orderRequest);
			$this->getOrderResponse = $this->sdk->getOrders()->createOrder($orderRequest, null);
			$this->logger->info('Criou pedido ' . $this->getOrderResponse->id);

			$order->setPagarmeTransaction($this->getOrderResponse);

			if ($this->getOrderResponse->status == 'paid') {
				$this->createInvoice($order);
			}

			$this->handlePaymentStatus($payment);
			//$this->insertCardInfosOnPayment($payment, $this->getOrderResponse->card);

			//$paymentAdditionalInfo = $this->getPaymentAdditionalInformation($infoInstance, $this->getOrderResponse);
			//$infoInstance->setAdditionalInformation($paymentAdditionalInfo);

			Mage::getModel('pagarmev5_core/transaction')->saveTransactionInformation($order, $infoInstance, $referenceKey, $this->getOrderResponse);
		} catch (\Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
			Mage::getSingleton('checkout/session')->setErrorMessage("<ul><li>" . $e->getMessage() . "</li></ul>");
			Mage::throwException($e->getMessage());
		}

		return $this;
	}

	/**
	 * @param Varien_Object $payment
	 * @param float $amount
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function capture(Varien_Object $payment, $amount) {
		$order = $payment->getOrder();
		$integerAmount = Mage::helper('pagarmev5_core')->parseAmountToCents($amount);

		$transactionId = Mage::getModel('pagarmev5_core/service_order')->getTransactionIdByOrder($order);

		$transactionModel = Mage::getModel('pagarmev5_core/service_transaction');

		try {
			$transactionId = $transactionModel->getTransactionById($transactionId);

			$this->getOrderResponse = $this->sdk->transactions()
				->capture([
					'id' => $transactionId,
					'amount' => $integerAmount
				]);

			return $this;
		} catch (\Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
		}
	}

	/**
	 * @param Varien_Object $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Exception
	 */
	public function refund(Varien_Object $payment, $amount) {
		$invoice = $payment->getOrder()
			->getInvoiceCollection()
			->getFirstItem();

		if (!$invoice->canRefund()) {
			Mage::throwException(
				Mage::helper('pagarmev5_core')->__('Invoice can\'t be refunded.')
			);
		}

		$amount = Mage::helper('pagarmev5_core')->parseAmountToCents($amount);

		try {
			$this->getOrderResponse = $this->sdk->transactions()->get(['id' => $invoice->getTransactionId()]);

			$this->sdk->transactions()
				->refund(
					[
						'id' => $this->getOrderResponse,
						'amount' => $amount,
					]);
		} catch (\Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
		}

		return $this;
	}
}
