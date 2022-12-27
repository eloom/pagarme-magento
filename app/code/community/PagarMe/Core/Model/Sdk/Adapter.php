<?php

class PagarMe_Core_Model_Sdk_Adapter extends Mage_Core_Model_Abstract
{
    /**
     * @var \PagarMe\Client
     */
    private $sdk;

    public function _construct() {
        parent::_construct();

        $apiKey = Mage::getStoreConfig('payment/pagarme/api_key');
        $this->sdk = new \PagarMe\Client(
            $apiKey,
            ['headers' => $this->getUserAgent()]
        );
    }

    /**
     * @return \PagarMe\Client
     */
    public function getSdk() {
        return $this->sdk;
    }

    /**
     * @return array
     */
    public function getUserAgent()
    {
        $userAgentValue = sprintf(
            'pagarme-magento/%s magento/%s',            
            Mage::getConfig()->getNode()->modules->PagarMe_Core->version,
            Mage::getVersion()
        );

        return [
            'User-Agent' => $userAgentValue,
            'X-PagarMe-User-Agent' => $userAgentValue,
            'X-PagarMe-Version' => '2019-09-01'
        ];
    }
}
