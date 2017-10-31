<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Carbon\Carbon;

class CurrencyController extends BaseController
{
	public static function getCurrencyRates($request, $response)
	{
		$valid = UserController::validate($request->getAttribute('name'), $request->getAttribute('token'));

		if (!$valid[0])
		{
			$arrRtn['msg'] = $valid[1];
			$status = 400;
		}
		else
		{
			$arrRtn = [];
			$arrRtn = $valid[1];
			$rates = self::getRates();
			$rates->rates->USD = 1.000;

			$currencies = self::getCurrencies();

			$arrRtn = [];
			foreach ($currencies as $currency) {
				$code = $currency->code;
				$arrRtn[$currency->name] = array('code' => $code, 'rate' => $rates->rates->$code);
			}

			$status = 200;
		}

		return $response->withJSON( $arrRtn )->withStatus($status);
	}

	public static function getRates()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.fixer.io/latest?base=USD');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		return json_decode(curl_exec($ch));
	}

	public static function getCurrencies()
	{
		return \ORM::for_table('currency')->find_many();
	}

}