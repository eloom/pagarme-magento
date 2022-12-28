<?php

class PagarMe_Boleto_Model_Boleto extends PagarMe_Core_Model_AbstractPaymentMethod
{
    use PagarMe_Core_Trait_ConfigurationsAccessor;

    private $logger;

    const BOLETO = 'pagarme_boleto';
    protected $_code = self::BOLETO;
    protected $_formBlockType = 'pagarme_boleto/form';
    protected $_infoBlockType = 'pagarme_boleto/info';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canUseForMultishipping = true;
    protected $_canManageRecurringProfiles = true;
    protected $_isInitializeNeeded = true;

    const POSTBACK_ENDPOINT = 'transaction_notification';

    /**
     * @var \PagarMe\Client
     */
    protected $sdk;

    /**
     * @var stdClass
     */
    protected $transaction;

    /**
     * @var PagarMe_Core_Helper_Data
     */
    protected $pagarmeCoreHelper;

    /**
     * @var PagarMe_Core_Helper_BusinessCalendar
     */
    protected $businessCalendar;

    public function __construct() {
        $this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);

        $this->sdk = Mage::getModel('pagarme_core/sdk_adapter')->getSdk();
        $this->pagarmeCoreHelper = Mage::helper('pagarme_core');
        $this->businessCalendar = new PagarMe_Core_Helper_BusinessCalendar();
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
    public function initialize($paymentAction, $stateObject)
    {
        $this->stateObject = $stateObject;

        $payment = $this->getInfoInstance();

        $this->stateObject->setState(
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
        );
        $this->stateObject->setStatus(
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
        );
        $this->stateObject->setIsNotified(true);

        $this->authorize($payment);

        $payment->setAmountAuthorized(
            $payment->getOrder()->getTotalDue()
        );

        return $this;
    }

   /**
    * Retrieve payment method title
    *
    * @return string
    */
    public function getTitle()
    {
        return Mage::getStoreConfig(
            'payment/pagarme_boleto/title'
        );
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
    private function setOrderAsPendingPayment($amount, $order)
    {
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
    public function getReferenceKey()
    {
        return Mage::getModel('pagarme_core/transaction')
            ->getReferenceKey();
    }

    /**
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     *
     * @throws Mage_Core_Exception
     */
    public function authorize(Varien_Object $payment) {
        try {
            $order = $payment->getOrder();
            $infoInstance = $this->getInfoInstance();
            $billingAddress = $order->getBillingAddress();
            $referenceKey = $this->getReferenceKey();

            $telephone = $billingAddress->getTelephone();
            $customer = $this->pagarmeCoreHelper->prepareCustomerData([
                'customer_type' => $this->pagarmeCoreHelper->getCustomerType($order->getCustomerTaxvat()),
                'customer_document_number' => $order->getCustomerTaxvat(),
                'customer_document_type' => $this->pagarmeCoreHelper->getDocumentType($order->getCustomerTaxvat()),
                'customer_name' => $this->pagarmeCoreHelper->getCustomerNameFromQuote($order),
                'customer_email' => $order->getCustomerEmail(),
                'customer_address_country' => $billingAddress->getCountry(),
                'customer_phone_ddd' => $this->pagarmeCoreHelper->getDddFromPhoneNumber($telephone),
                'customer_phone_number' => $this->pagarmeCoreHelper->getPhoneWithoutDdd($telephone),
            ]);
            $amount = $this->pagarmeCoreHelper->parseAmountToCents($order->getGrandTotal());

            $this->transaction = $this->sdk
                ->transactions()
                ->create([
                    'payment_method' => 'boleto',
                    'boleto_expiration_date' => $this->getBoletoExpirationDate(),
                    'boleto_instructions' => $this->getBoletoInstructions(),
                    'amount' => $amount,
                    'customer' => $customer,
                    'async' => false,
                    'postback_url' => $this->getUrlForPostback(),
                    'metadata' => [
                        'reference_key' => $referenceKey,
                        'order_id' => $order->getIncrementId()
                    ]
                ]);

            $this->setOrderAsPendingPayment($amount, $order);

            $infoInstance->setAdditionalInformation(
                $this->extractAdditionalInfo($infoInstance, $this->transaction, $order)
            );
            Mage::getModel('pagarme_core/transaction')
                ->saveTransactionInformation(
                    $order,
                    $infoInstance,
                    $referenceKey,
                    $this->transaction
                );
        } catch (\Exception $e) {
            $this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment $infoInstance
     * @param stdClass $transaction
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function extractAdditionalInfo($infoInstance, $transaction, $order)
    {
        $data = [
            'pagarme_transaction_id' => $transaction->id,
            'store_order_id' => $order->getId(),
            'store_increment_id' => $order->getIncrementId(),
            'pagarme_boleto_url' => $transaction->boleto_url,
        ];

        return array_merge(
            $infoInstance->getAdditionalInformation(),
            $data
        );
    }

    /**
     * @param DateTime $date
     *
     * @return string
     */
    private function getBoletoExpirationDate($date = null)
    {
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
    private function getInitialBoletoExpirationDate()
    {
        $boletoExpirationDate = new DateTime(
            'now',
            new DateTimeZone('America/Sao_Paulo')
        );

        return $boletoExpirationDate->modify(
            '+'.$this->getDaysToBoletoExpire().' days'
        );
    }


}
