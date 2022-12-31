<?php

class PagarMe_Core_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @param array $data
     *
     * @return \array
     */
    public function prepareCustomerData($data) {
        return [
            'external_id' => $data['customer_id'],
            'type' => $data['customer_type'],
            'name' => $data['customer_name'],
            'email' => $data['customer_email'],
            'country' => strtolower($data['customer_address_country']),
            'documents' => [
                [
                    'type' => $data['customer_document_type'],
                    'number' => Zend_Filter::filterStatic(
                        $data['customer_document_number'],
                        'Digits'
                    )
                ]
            ],
            'phone_numbers' => [
                '+55' . $data['customer_phone_ddd'] . $data['customer_phone_number'],// FIXME: DDI precisa ser dinâmico
            ]
        ];
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
