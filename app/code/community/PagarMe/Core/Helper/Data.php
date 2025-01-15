<?php

use PagarmeCoreApiLib\Models\CreateAddressRequest;
use PagarmeCoreApiLib\Models\CreateCustomerRequest;
use PagarmeCoreApiLib\Models\CreateOrderItemRequest;
use PagarmeCoreApiLib\Models\CreatePhoneRequest;
use PagarmeCoreApiLib\Models\CreatePhonesRequest;

class PagarMe_Core_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param array $data
     *
     * @return CreateCustomerRequest
     */
    public function prepareCustomerData($data)
    {
        $billingAddress = $data['customer_address'];

        $address = new CreateAddressRequest($billingAddress->getStreet(1),
            $billingAddress->getStreet(2),
            preg_replace('/\D/', '', $billingAddress->getPostcode()),
            $billingAddress->getStreet(3),
            $billingAddress->getCity(),
            $billingAddress->getState(),
            $billingAddress->getCountry(),
            $billingAddress->getStreet(4),
            null,
            null,
            null);

        $phones = new CreatePhonesRequest(new CreatePhoneRequest('55',
            $data['customer_phone_number'],
            $data['customer_phone_ddd']),
            new CreatePhoneRequest('55',
                $data['customer_phone_number'],
                $data['customer_phone_ddd']));

        $customer = new CreateCustomerRequest();
        $customer->type = $data['customer_type'];
        $customer->document_type = $data['customer_document_type'];
        $customer->document = Zend_Filter::filterStatic($data['customer_document_number'], 'Digits');
        $customer->name = $data['customer_name'];
        $customer->email = $data['customer_email'];
        $customer->address = $address;
        $customer->phones = $phones;

        return $customer;
    }

    /**
     * @param $shippingAddress
     * @return CreateAddressRequest
     */
    public function prepareAddressData($shippingAddress)
    {
        $helper = Mage::helper('eloombootstrap');

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

        return $addressRequest;
    }

    /**
     * @param $order
     * @param $referenceKey
     * @return array
     */
    public function prepareMetadata($order, $referenceKey) {
        return [
            'order_id' => $order->getIncrementId(),
            'reference_key' => $referenceKey
        ];
    }

    /**
     * @param $visibleItems
     * @return CreateOrderItemRequest[]
     */
    public function prepareOrderItems($order)
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $qtd = $item->getQtyToInvoice();
            $basePrice = round($item->getPrice(), 2);
            if (!empty($qtd) && $basePrice > 0) {
                $item = new CreateOrderItemRequest();
                $item->code = $item->getProductId();
                $item->amount = $this->parseAmountToCents($basePrice);
                $item->description = substr($item->getName(), 0, 255);
                $item->quantity = $qtd;
                //$item->category = 'Camisetas';

                $items[] = $item;
            }
        }

        /**
         * Gift Wrap AH
         */
        if ($order->getBaseAwGiftwrapAmount()) {
            $item = new CreateOrderItemRequest();
            $item->code = 'GIFTWRAP';
            $item->amount = $this->parseAmountToCents($order->getAwGiftwrapAmount());
            $item->description = 'Embalagem especial';
            $item->quantity = 1;

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param float $amount
     *
     * @return int
     */
    public function parseAmountToCents($amount)
    {
        return intval($amount * 100);
    }

    /**
     * @param int $amount
     *
     * @return float
     */
    public function parseAmountToCurrency($amount)
    {
        return floatval($amount / 100);
    }

    /**
     * @param string $phone
     *
     * @return string
     */
    public function getDddFromPhoneNumber($phone)
    {
        preg_replace("/[^0-9]/", "", $phone);
        return substr(Zend_Filter::filterStatic($phone, 'Digits'), 0, 2);
    }

    /**
     * @param string $phone
     *
     * @return string
     */
    public function getPhoneWithoutDdd($phone)
    {
        preg_replace("/[^0-9]/", "", $phone);
        return substr(Zend_Filter::filterStatic($phone, 'Digits'), 2);
    }

    /**
     * @param string $quote
     *
     * @return string
     */
    public function getDocumentType($taxVat)
    {
        $documentNumber = preg_replace('/\D/', '', $taxVat);
        if (strlen($documentNumber) == 11) {
            return 'cpf';
        }

        return 'cnpj';
    }

    /**
     * @param string $quote
     *
     * @return string
     */
    public function getCustomerType($taxVat)
    {
        $documentNumber = preg_replace('/\D/', '', $taxVat);
        if (strlen($documentNumber) == 11) {
            return 'individual';
        }

        return 'corporation';
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return string
     */
    public function getCustomerNameFromQuote($order)
    {
        return implode([
            $order->getCustomerFirstname(),
            $order->getCustomerMiddlename(),
            $order->getCustomerLastname()
        ], ' ');
    }

    public function formatFloatToCurrentLocale($number)
    {
        $currencyHelper = Mage::getModel('directory/currency');
        return $currencyHelper->formatTxt($number, [
            'display' => Zend_Currency::NO_SYMBOL
        ]);
    }
}
