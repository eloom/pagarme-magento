<?php

use PagarMe_Core_Model_System_Config_Source_PaymentAction as PaymentActionConfig;
use PagarMe_Creditcard_Model_Exception_InvalidInstallments as InvalidInstallmentsException;
use PagarMe_Creditcard_Model_Exception_TransactionsInstallmentsDivergent as TransactionsInstallmentsDivergent;
use PagarmeCoreApiLib\Models\CreateAddressRequest;
use PagarmeCoreApiLib\Models\CreateCreditCardPaymentRequest;
use PagarmeCoreApiLib\Models\CreateOrderItemRequest;
use PagarmeCoreApiLib\Models\CreateOrderRequest;
use PagarmeCoreApiLib\Models\CreatePaymentRequest;
use PagarmeCoreApiLib\Models\CreateShippingRequest;
use PagarmeCoreApiLib\Models\GetOrderResponse;
use PagarmeCoreApiLib\PagarmeCoreApiClient;

class PagarMe_Creditcard_Model_Creditcard extends PagarMe_Core_Model_AbstractPaymentMethod
{

    private $logger;

    use PagarMe_Core_Trait_ConfigurationsAccessor;

    const CC = 'pagarme_creditcard';

    /**
     * @var string
     */
    protected $_code = self::CC;

    /**
     * @var string
     */
    protected $_formBlockType = 'pagarme_creditcard/form';

    /**
     * @var string
     */
    protected $_infoBlockType = 'pagarme_creditcard/info';

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
     * @var PagarMe_Core_Helper_Data
     */
    protected $pagarmeCoreHelper;

    /**
     * @var PagarMe_Creditcard_Helper_Data
     */
    protected $pagarmeCreditCardHelper;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * @var PagarMe_Core_Model_Transaction
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

    public function __construct()
    {
        $this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);

        $this->sdk = Mage::getModel('pagarme_core/sdk_adapter')->getSdk();
        $this->pagarmeCoreHelper = Mage::helper('pagarme_core');
        $this->pagarmeCreditCardHelper = Mage::helper('pagarme_creditcard');
        $this->transactionModel = Mage::getModel('pagarme_core/transaction');
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
    public function initialize($paymentAction, $stateObject)
    {
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
    public function setGetOrderResponse(GetOrderResponse $getOrderResponse)
    {
        $this->getOrderResponse = $getOrderResponse;
    }

    /**
     * @return string
     */
    protected function getPostbackCode()
    {
        return self::POSTBACK_ENDPOINT;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function assignData($data)
    {
        $additionalInfoData = [
            'card_hash' => $data['card_hash'],
            'installments' => $data['installments']
        ];
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation($additionalInfoData);

        return $this;
    }

    public function getMaxInstallment()
    {
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
    public function isInstallmentsValid($installments)
    {
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
                Mage::helper('pagarme_creditcard')
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
    public function generateCard($cardHash)
    {
        try {
            $card = $this->sdk->cards()->get(['id' => $cardHash]);
            return $card;
        } catch (\Exception $e) {
            $this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
        }
    }

    /**
     * @param int $installments
     * @return void
     * @throws TransactionsInstallmentsDivergent
     */
    // FIXME: remove essa checagem
    public function checkInstallments($installments)
    {
        if ($this->getOrderResponse->installments != $installments) {
            $message = $this->pagarmeCoreHelper->__(
                'Installments is Diverging'
            );
            throw new TransactionsInstallmentsDivergent($message);
        }
    }

    /**
     * Return if a given transaction was paid
     *
     * @return bool
     */
    public function transactionIsPaid()
    {
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
    protected function createInvoice($order)
    {
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
    public function getReferenceKey()
    {
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
    public function insertCardInfosOnPayment($payment, $card)
    {
        $payment
            ->setCcType($card->brand)
            ->setCcOwner($card->holder_name)
            ->setCcLast4($card->last_digits);

        return $payment;
    }

    /**
     * @return string
     */
    private function buildCheckoutRefusedMessage()
    {
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
    private function handlePaymentStatus(Mage_Sales_Model_Order_Payment $payment)
    {
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
    )
    {
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
    public function authorize(Mage_Sales_Model_Order_Payment $payment, $amount)
    {
        $paymentActionConfig = $this->getPaymentActionConfig();
        $captureTransaction = 'auth_and_capture';
        if ($paymentActionConfig === PaymentActionConfig::AUTH_ONLY) {
            $captureTransaction = 'auth_only';
        }
        $infoInstance = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCapture($paymentActionConfig);
        $referenceKey = $this->getReferenceKey();
        $cardHash = $infoInstance->getAdditionalInformation('card_hash');
        $installments = (int)$infoInstance->getAdditionalInformation('installments');

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = null;
        if ($order->getIsVirtual()) {
            $shippingAddress = $order->getBillingAddress();
        } else {
            $shippingAddress = $order->getShippingAddress();
        }

        try {
            $helper = Mage::helper('eloombootstrap');

            $this->isInstallmentsValid($installments);
            //$card = $this->generateCard($cardHash); // FIXME: rever

            $telephone = preg_replace("/[^0-9]/", "", $billingAddress->getTelephone());
            $customerName = $this->pagarmeCoreHelper->getCustomerNameFromQuote($order);

            $customer = $this->pagarmeCoreHelper->prepareCustomerData([
                'customer_id' => $order->getCustomerEmail(),
                'customer_type' => $this->pagarmeCoreHelper->getCustomerType($order->getCustomerTaxvat()),
                'customer_document_number' => $order->getCustomerTaxvat(),
                'customer_document_type' => $this->pagarmeCoreHelper->getDocumentType($order->getCustomerTaxvat()),
                'customer_name' => $customerName,
                'customer_email' => $order->getCustomerEmail(),
                'customer_phone_ddd' => $this->pagarmeCoreHelper->getDddFromPhoneNumber($telephone),
                'customer_phone_number' => $this->pagarmeCoreHelper->getPhoneWithoutDdd($telephone),
                'customer_address' => $billingAddress
            ]);

            $amount = $this->pagarmeCoreHelper->parseAmountToCents($amount);

            $items = $this->pagarmeCoreHelper->prepareOrderItems($order);

            $creditCardPayment = new CreateCreditCardPaymentRequest();
            $creditCardPayment->operation_type = 'auth_and_capture';
            $creditCardPayment->installments = $installments;
            $creditCardPayment->card_token = $cardHash;

            $paymentRequest = new CreatePaymentRequest();
            $paymentRequest->paymentMethod = 'credit_card';
            $paymentRequest->amount = $amount;
            $paymentRequest->creditCard = $creditCardPayment;

            $addressRequest = new CreateAddressRequest();
            $addressRequest->street = $shippingAddress->getStreet(1);
            $addressRequest->number = $shippingAddress->getStreet(2);
            $addressRequest->zipCode = preg_replace('/\D/', '', $shippingAddress->getPostcode());
            $addressRequest->city = $shippingAddress->getCity();
            $addressRequest->state = $shippingAddress->getState();
            $addressRequest->country = $shippingAddress->getCountry();
            if (!$helper->isEmpty($shippingAddress->getStreet(4))) {
                $addressRequest->neighborhood = $shippingAddress->getStreet(4);
            }
            if (!$helper->isEmpty($shippingAddress->getStreet(3))) {
                $addressRequest->complement = $shippingAddress->getStreet(3);
            }

            $shippingRequest = new CreateShippingRequest();
            $shippingRequest->amount = $this->pagarmeCoreHelper->parseAmountToCents($order->getShippingAmount());
            $shippingRequest->description = $order->getShippingDescription();
            $shippingRequest->recipientName = $customerName;
            $shippingRequest->recipientPhone = $customer['customer_phone_ddd'] . $customer['customer_phone_number'];
            $shippingRequest->address = $addressRequest;

            $orderRequest = new CreateOrderRequest();
            $orderRequest->code = $order->getIncrementId();
            $orderRequest->closed = true;
            $orderRequest->ip = $order->getRemoteIp();
            $orderRequest->items = $items;
            $orderRequest->customer = $customer;
            $orderRequest->shipping = $shippingRequest;
            $orderRequest->payments = [$paymentRequest];

            $this->getOrderResponse = $this->sdk->getOrders()->createOrder($orderRequest, null);

            $order->setPagarmeTransaction($this->getOrderResponse);
            $this->checkInstallments($installments);

            if ($this->getOrderResponse->status == 'paid') {
                $this->createInvoice($order);
            }

            $this->handlePaymentStatus($payment);
            $this->insertCardInfosOnPayment($payment, $this->getOrderResponse->card);

            $paymentAdditionalInfo = $this->getPaymentAdditionalInformation($infoInstance, $this->getOrderResponse);
            $infoInstance->setAdditionalInformation($paymentAdditionalInfo);

            $this->transactionModel->saveTransactionInformation($order, $infoInstance, $referenceKey, $this->getOrderResponse);
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
    public function capture(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        $integerAmount = Mage::helper('pagarme_core')->parseAmountToCents($amount);

        $transactionId = Mage::getModel('pagarme_core/service_order')->getTransactionIdByOrder($order);

        $transactionModel = Mage::getModel('pagarme_core/service_transaction');

        try {
            $this->getOrderResponse = $transactionModel->getTransactionById($transactionId);

            $this->getOrderResponse = $this->sdk->transactions()
                ->capture([
                    'id' => $this->getOrderResponse,
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
    public function refund(Varien_Object $payment, $amount)
    {
        $invoice = $payment->getOrder()
            ->getInvoiceCollection()
            ->getFirstItem();

        if (!$invoice->canRefund()) {
            Mage::throwException(
                Mage::helper('pagarme_core')->__('Invoice can\'t be refunded.')
            );
        }

        $amount = Mage::helper('pagarme_core')->parseAmountToCents($amount);

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
