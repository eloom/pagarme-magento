<?php

class PagarMeV5_Core_Transaction_NotificationController extends Mage_Core_Controller_Front_Action {

	private $logger;

	const WEBHOOKS = ['order.canceled', 'order.paid', 'order.payment_failed'];

	/**
	 * Initialize resource model
	 */
	protected function _construct() {
		$this->logger = Eloom_Bootstrap_Logger::getLogger(__CLASS__);
		parent::_construct();
	}

	/**
	 * @return Zend_Controller_Response_Abstract
	 * @throws Zend_Controller_Response_Exception
	 */
	public function postbackAction() {
		$request = $this->getRequest();

		if (!$request->isPost()) {
			return $this->getResponse()->setHttpResponseCode(405);
		}

		$body = json_decode($request->getRawBody(), false);

		if ($this->isInvalidRequest($body)) {
			return $this->getResponse()->setHttpResponseCode(400);
		}

		$this->logger->info(sprintf("Processando notificação. Transação [%s] - Status [%s].", $body->data->id, $body->data->status));

		try {
			Mage::getModel('pagarmev5_core/postback')->processPostback($body->data->id, $body->data->status, $body->type);
			return $this->getResponse()->setBody('ok');
		} catch (PagarMeV5_Core_Model_PostbackHandler_Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
			$this->logger->fatal($e->getTraceAsString());

			return $this->getResponse()
				->setHttpResponseCode(200)
				->setBody($e->getMessage());
		} catch (Exception $e) {
			$this->logger->fatal($e->getCode() . ' - ' . $e->getMessage());
			//$this->logger->fatal($e->getTraceAsString());

			return $this->getResponse()
				->setHttpResponseCode(500)
				->setBody($e->getMessage());
		}
	}

	/**
	 * @param Mage_Core_Controller_Request_Http $request
	 *
	 * @return bool
	 */
	protected function isInvalidRequest(stdClass $body) {
		if (!in_array($body->type, self::WEBHOOKS)) {
			return false;
		}
		if ($body->data->id != null) {
			return false;
		}

		if ($body->data->status != null) {
			return false;
		}

		/*
		$signature = $request->getHeader('X-Hub-Signature');

		if ($signature == false) {
			return false;
		}

		if (!$this->isAuthenticRequest($request, $signature)) {
			return false;
		}
		*/

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
	) {
		return Mage::getModel('pagarmev5_core/sdk_adapter')->getSdk()
			->postbacks()
			->validate($request->getRawBody(), $signature);
	}
}
