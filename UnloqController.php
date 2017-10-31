<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class UnloqController extends BaseController
{
	public static function authorize($email)
	{
		$key = CryptedController::getUnloqKey();
		\App\Handlers\LogController::info('unloq: '.$key);

		$query_parameters = '{"method":"UNLOQ", "ip": "'.gethostbyname(gethostname()).'", "generate_token":"true", "email": "'.$email.'"}';
		\App\Handlers\LogController::info('unloq: '.$query_parameters);

		$url = 'https://api.unloq.io/v1/authenticate';

		$header = ['Authorization: Bearer '.$key, "Content-Type: application/json"];

		$sprintString = '%s';
		$parametersArr = [$url];

		$unloqResponse = GetController::doPost($sprintString, $parametersArr, $header, $query_parameters, '');
		\App\Handlers\LogController::info('unloq: '.$unloqResponse);

		if (strpos($unloqResponse, '503')):
			return 503;
		endif;

		$decode = json_decode($unloqResponse);
		if ($decode->error):
			$arrRtn['error'] = $decode->error->message;
		else:
			$arrRtn['unloqID'] = $decode->result->unloq_id;
			$arrRtn['unloqKey'] = $decode->result->token;
		endif;

		return $arrRtn;
		
	}

	public static function webHook($request, $response)
	{
		$body = $request->getBody();
		\App\Handlers\LogController::info('unloq (webhook): '.$body);
	}

	public static function getUnloqCredit()
	{
		return \ORM::for_table('unloqCredits')->find_one(1);
	}
}