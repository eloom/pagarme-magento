<?php

##eloom.licenca##

class PagarMeV5_Creditcard_Helper_Math extends Mage_Core_Helper_Abstract {

	/**
	 * Calcula valor da parcela para financiamento em parcelas fixas.
	 *
	 * @param total
	 *            Valor total financiado
	 * @param interestRate
	 *            Taxa de juros
	 * @param numberOfPayments
	 *            Número de parcelas
	 * @return valor da parcela
	 */
	public static function calculatePayment($total, $interestRate, $numberOfPayments) {
		$payment = 0;
		if ($interestRate != 0) {
				// Calculo do valor da parcela - Tabela Price { R = P x [ i (1 + i)n ] ÷ [ (1 + i )n -1] }
				$payment = round($total * (($interestRate * pow((1 + $interestRate), $numberOfPayments)) / (pow((1 + $interestRate), $numberOfPayments) - 1)), 2);
		} else {
			$payment = $total / $numberOfPayments;
		}

		return $payment;
	}

	/**
	 * Returns the first found number from an string
	 * Parsing depends on given locale (grouping and decimal)
	 *
	 * Examples for input:
	 * '  2345.4356,1234' = 23455456.1234
	 * '+23,3452.123' = 233452.123
	 * ' 12343 ' = 12343
	 * '-9456km' = -9456
	 * '0' = 0
	 * '2 054,10' = 2054.1
	 * '2'054.52' = 2054.52
	 * '2,46 GB' = 2.46
	 *
	 * @param string|int $value
	 * @return float
	 */
	public static function formatPriceToUS($value) {
		if (is_null($value)) {
			return null;
		}
		if (!is_string($value)) {
			return floatval($value);
		}
		$value = str_replace('\'', '', $value);
		$value = str_replace(' ', '', $value);

		$separatorComa = strpos($value, ',');
		$separatorDot = strpos($value, '.');

		if ($separatorComa !== false && $separatorDot !== false) {
			if ($separatorComa > $separatorDot) {
				$value = str_replace('.', '', $value);
				$value = str_replace(',', '.', $value);
			} else {
				$value = str_replace(',', '', $value);
			}
		} elseif ($separatorComa !== false) {
			$value = str_replace(',', '.', $value);
		}

		return floatval($value);
	}

	/*
	 * Converte o valor do formato enviado em centavos para o formato numérico.
	 * Exemplo: 49999 para 499.99
	 */

	public static function convertStringCentsToDouble($cents) {
		$value = self::formatPriceToUS($cents);
		$value = str_ireplace(".", "", $value);
		return $value / 100;
	}

	/**
	 * Formata um valor para ter no máximo dois dígitos após a vírgula.
	 */
	public static function formatAmount($amount) {
		return round($amount, 2);
	}

	/**
	 * Recebe um numérico e retorna o mesmo valor em centavos.
	 * Entrada: 12,34, saída 1234
	 */
	public static function formatPaymentAmountToCents($amount) {
		$amountInt = (int) $amount;
		if ($amountInt == $amount) {
			$amount = (int) $amount;
			$amountStr = $amount;
			$amountStr = str_ireplace(",", "", $amountStr);
			$amountStr = str_ireplace(".", "", $amountStr);
			$amountStr = $amountStr . '00';
		} else {
			$amount = self::formatAmount($amount);
			$amountStr = $amount;

			$pos = strpos($amountStr, '.');
			$decimais = substr($amountStr, $pos + 1);
			$tamDecimais = strlen($decimais);

			while ($tamDecimais < 2) {
				$amountStr = $amountStr . '0';

				$pos = strpos($amountStr, '.');
				$decimais = substr($amountStr, $pos + 1);
				$tamDecimais = strlen($decimais);
			}

			$amountStr = str_ireplace(",", "", $amountStr);
			$amountStr = str_ireplace(".", "", $amountStr);
		}

		return $amountStr;
	}
}
