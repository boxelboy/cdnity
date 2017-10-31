<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Carbon\Carbon;
use \MessageBird\Client;

class SMSController extends BaseController
{
	public static function sendSMS($num, $token)
	{

		$arrRtn = [];
		$smsKey = CryptedController::getSMSKey('live');

		$MessageBird = new \MessageBird\Client($smsKey);

		$Message             = new \MessageBird\Objects\Message();
		$Message->originator = 'CDNity';
		$Message->recipients = array($num);
		$Message->body       = $token.' This code is valid for 24 hours';

		try {
		    $MessageResult = $MessageBird->messages->create($Message);
		    if ($MessageResult->recipients->items[0]->status == "sent"):
		    	$arrRtn['sms'] = 'sent';
		    	$status = 200;
		    	\App\Handlers\LogController::info('sms sent to '.$num);
		    endif;

		} catch (\MessageBird\Exceptions\AuthenticateException $e) {
		    // That means that your accessKey is unknown
	    	$arrRtn['sms'] = 'wrong key';
	    	$status = 400;
	    	\App\Handlers\LogController::error('sms: invalid key');
		} catch (\MessageBird\Exceptions\BalanceException $e) {
		    // That means that you are out of credits, so do something about it.
	    	$arrRtn['sms'] = 'no sms credit';
	    	$status = 400;
	    	\App\Handlers\LogController::error('sms: no sms credit');
		} catch (\Exception $e) {
	    	$arrRtn['sms'] = $e->getMessage();
	    	$status = 400;
		}

		return $arrRtn;
	}
}