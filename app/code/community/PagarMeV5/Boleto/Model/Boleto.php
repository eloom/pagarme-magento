<?php

use PagarmeCoreApiLib\Models\CreateAddressRequest;
use PagarmeCoreApiLib\Models\CreateBoletoPaymentRequest;
use PagarmeCoreApiLib\Models\CreateOrderItemRequest;
use PagarmeCoreApiLib\Models\CreateOrderRequest;
use PagarmeCoreApiLib\Models\CreatePaymentRequest;
use PagarmeCoreApiLib\Models\CreateShippingRequest;
use PagarmeCoreApiLib\Models\GetOrderResponse;
use PagarmeCoreApiLib\PagarmeCoreApiClient;

class PagarMeV5_Boleto_Model_Boleto extends PagarMeV5_Core_Model_AbstractPaymentMethod {
	use PagarMeV5_Core_Trait_ConfigurationsAccessor;

	private $logger;

	const BOLETO = 'pagarmev5_boleto';
	protected $_code = self::BOLETO;
	protected $_formBlockType = 'pagarmev5_boleto/form';
	protected $_infoBlockType = 'pagarmev5_boleto/info';
	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canRefund = true;
	protected $_canUseForMultishipping = true;
	protected $_canManageRecurringProfiles = true;
	protected $_isInitializeNeeded = true;

	const POSTBACK_ENDPOINT = 'transaction_notification';

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
	 * @var PagarMeV5_Core_Helper_BusinessCalendar
	 */
	protected $businessCalendar;

	public function __construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);

		$this->sdk = Mage::getModel('pagarmev5_core/sdk_adapter')->getSdk();
		$this->pagarmeCoreHelper = Mage::helper('pagarmev5_core');
		$this->businessCalendar = new PagarMeV5_Core_Helper_BusinessCalendar();
	}

	/**
	 * Method that will be executed instead of magento's authorize default
	 * workflow
	 *
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 *
	 * @return Mage_Payment_Model_Method_Abstract
	 */
	public function initialize($paymentAction, $stateObject) {
		$this->stateObject = $stateObject;

		$payment = $this->getInfoInstance();

		$this->stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		$this->stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		$this->stateObject->setIsNotified(true);

		$this->authorize($payment, $payment->getOrder()->getGrandTotal());
		$payment->setAmountAuthorized($payment->getOrder()->getTotalDue());

		return $this;
	}

	/**
	 * Retrieve payment method title
	 *
	 * @return string
	 */
	public function getTitle() {
		return Mage::getStoreConfig('payment/pagarmev5_boleto/title');
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
			'pagarme_payment_method' => self::BOLETO
		];

		$this->getInfoInstance()
			->setAdditionalInformation($additionalInfoData);

		return $this;
	}

	/**
	 * Given a boleto, set its related order status as pending_payment
	 *
	 * @param int $amount
	 * @param Mage_Sales_Model_Order $order
	 */
	private function setOrderAsPendingPayment($amount, $order) {
		$message = 'Boleto is waiting payment';
		$notifyCustomer = true;
		$order->setState(
			Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
			Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
			$this->pagarmeCoreHelper->__($message, $amount),
			$notifyCustomer
		);
	}

	/**
	 * @return string
	 */
	public function getReferenceKey() {
		return Mage::getModel('pagarmev5_core/transaction')->getReferenceKey();
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
		try {
			$order = $payment->getOrder();
			$infoInstance = $this->getInfoInstance();
			$billingAddress = $order->getBillingAddress();
			$shippingAddress = null;
			if ($order->getIsVirtual()) {
				$shippingAddress = $order->getBillingAddress();
			} else {
				$shippingAddress = $order->getShippingAddress();
			}
			$referenceKey = $this->getReferenceKey();

			$telephone = $billingAddress->getTelephone();
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

			$createBoletoPaymentRequest = new CreateBoletoPaymentRequest();
			$createBoletoPaymentRequest->due_at = $this->getBoletoExpirationDate();
			$createBoletoPaymentRequest->instructions = $this->getBoletoInstructions();

			$paymentRequest = new CreatePaymentRequest();
			$paymentRequest->paymentMethod = 'boleto';
			$paymentRequest->amount = $amount;
			$paymentRequest->boleto = $createBoletoPaymentRequest;

			$addressRequest = $this->pagarmeCoreHelper->prepareAddressData($shippingAddress);

			$shippingRequest = new CreateShippingRequest();
			$shippingRequest->amount = $this->pagarmeCoreHelper->parseAmountToCents($order->getShippingAmount());
			$shippingRequest->description = $order->getShippingDescription();
			$shippingRequest->recipientName = $customerName;
			$shippingRequest->recipientPhone = $customerPhoneDdd . $customerPhoneNumber;
			$shippingRequest->address = $addressRequest;

			$orderRequest = new CreateOrderRequest();
			$orderRequest->code = $order->getIncrementId();
			$orderRequest->closed = true;
			$orderRequest->ip = $order->getRemoteIp();
			$orderRequest->items = $items;
			$orderRequest->customer = $customer;
			$orderRequest->shipping = $shippingRequest;
			$orderRequest->payments = [$paymentRequest];
			$orderRequest->metadata = $this->pagarmeCoreHelper->prepareMetadata($order, $referenceKey);

			$this->getOrderResponse = $this->sdk->getOrders()->createOrder($orderRequest, null);
			$this->logger->info('Criou pedido ' . $this->getOrderResponse->id);

			$this->setOrderAsPendingPayment($amount, $order);

			$infoInstance->setAdditionalInformation($this->extractAdditionalInfo($infoInstance, $this->getOrderResponse, $order));

			Mage::getModel('pagarmev5_core/transaction')->saveTransactionInformation($order, $infoInstance, $referenceKey, $this->getOrderResponse);
		} catch (\Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
			Mage::getSingleton('checkout/session')->setErrorMessage("<ul><li>" . $e->getMessage() . "</li></ul>");
			Mage::throwException($e->getMessage());
		}

		return $this;
	}

	/**
	 * @param Mage_Sales_Model_Order_Payment $infoInstance
	 * @param GetOrderResponse $orderResponse
	 * @param Mage_Sales_Model_Order $order
	 *
	 * @return array
	 */
	private function extractAdditionalInfo($infoInstance, $orderResponse, $order) {
		$data = [
			'pagarme_transaction_id' => $orderResponse->id,
			'store_order_id' => $order->getId(),
			'store_increment_id' => $order->getIncrementId(),
			'pagarme_boleto_url' => $orderResponse->charges[0]->lastTransaction->url,
		];

		return array_merge($infoInstance->getAdditionalInformation(), $data);
	}

	/**
	 * @param DateTime $date
	 *
	 * @return string
	 */
	private function getBoletoExpirationDate($date = null) {
		$boletoExpirationDate = !is_null($date) ?
			$date :
			$this->getInitialBoletoExpirationDate();

		if ($this->businessCalendar->isBusinessDay($boletoExpirationDate)) {
			return $boletoExpirationDate->format('Y-m-d');
		}

		$boletoExpirationDate->modify('+1 days');

		return $this->getBoletoExpirationDate($boletoExpirationDate);
	}

	/**
	 * @return DateTime
	 */
	private function getInitialBoletoExpirationDate() {
		$boletoExpirationDate = new DateTime(
			'now',
			new DateTimeZone('America/Sao_Paulo')
		);

		return $boletoExpirationDate->modify(
			'+' . $this->getDaysToBoletoExpire() . ' days'
		);
	}
}
