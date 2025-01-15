<?php

class PagarMe_Core_Transaction_NotificationController extends Mage_Core_Controller_Front_Action
{

    private $logger;

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
        parent::_construct();
    }

    /**
     * @return Zend_Controller_Response_Abstract
     * @throws Zend_Controller_Response_Exception
     */
    public function postbackAction()
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $this->getResponse()->setHttpResponseCode(405);
        }

        if (!$this->isValidRequest($request)) {
            return $this->getResponse()->setHttpResponseCode(400);
        }

        $data = $this->getRequest()->getPost();

        $this->logger->info(sprintf("Processando notificação. Transação [%s] - Status [%s].", $data['id'], $data['status']));

        $transactionId = $data['id'];
        $currentStatus = $data['status'];
        $type = $data['type'];

        try {
            Mage::getModel('pagarme_core/postback')
                ->processPostback(
                    $transactionId,
                    $currentStatus,
                    $type
                );
            return $this->getResponse()->setBody('ok');
        } catch (PagarMe_Core_Model_PostbackHandler_Exception $e) {
            $this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
            //$this->logger->fatal($e->getTraceAsString());

            return $this
                ->getResponse()
                ->setHttpResponseCode(200)
                ->setBody($e->getMessage());
        } catch (Exception $e) {
            $this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
            //$this->logger->fatal($e->getTraceAsString());

            return $this
                ->getResponse()
                ->setHttpResponseCode(500)
                ->setBody($e->getMessage());
        }
    }

    /**
     * @param Mage_Core_Controller_Request_Http $request
     *
     * @return bool
     */
    protected function isValidRequest(
        Mage_Core_Controller_Request_Http $request
    )
    {
        if ($request->getPost('id') == null) {
            return false;
        }

        if ($request->getPost('status') == null) {
            return false;
        }

        $signature = $request->getHeader('X-Hub-Signature');

        if ($signature == false) {
            return false;
        }

        if (!$this->isAuthenticRequest($request, $signature)) {
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Core_Controller_Request_Http $request
     * @param string $signature
     *
     * @return bool
     */
    protected function isAuthenticRequest(
        Mage_Core_Controller_Request_Http $request,
                                          $signature
    )
    {
        return Mage::getModel('pagarme_core/sdk_adapter')->getSdk()
            ->postbacks()
            ->validate($request->getRawBody(), $signature);
    }
}
