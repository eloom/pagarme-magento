<?php

class PagarMe_Core_Model_PostbackHandler_Factory {
	/**
	 * Instantiate a PostbackHandler based on desired status
	 *
	 * @param string $status
	 * @param string $type
	 * @param Mage_Sales_Model_Order $order
	 * @param int $transactionId
	 *
	 * @return PagarMe_Core_Model_PostbackHandler_Base
	 * @throws \Exception
	 */
	public static function createFromDesiredStatus(
		$status,
		$type,
		$order,
		$transactionId
	) {
		if ($status === 'paid') {
			return new PagarMe_Core_Model_PostbackHandler_Paid(
				$order,
				$transactionId,
				$type
			);
		}

		if ($status === 'authorized') {
			return new PagarMe_Core_Model_PostbackHandler_Authorized(
				$order,
				$transactionId,
				$type
			);
		}

		if ($status === 'refunded') {
			return new PagarMe_Core_Model_PostbackHandler_Refunded(
				$order,
				$transactionId,
				$type
			);
		}

		if ($status === 'refused') {
			return new PagarMe_Core_Model_PostbackHandler_Refused(
				$order,
				$transactionId,
				$type
			);
		}

		if ($status === 'analyzing') {
			return new PagarMe_Core_Model_PostbackHandler_Analyzing(
				$order,
				$transactionId,
				$type
			);
		}

		if ($status === 'pending_review') {
			return new PagarMe_Core_Model_PostbackHandler_PendingReview(
				$order,
				$transactionId,
				$type
			);
		}

		throw new \Exception(sprintf(
			'There\'s no postback handler for this desired status: %s',
			$status
		));
	}
}
