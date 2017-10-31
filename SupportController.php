<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Carbon\Carbon;

class SupportController extends BaseController
{
	public function emailTicket($request, $response)
	{
		$body = $request->getBody();
		$input = json_decode($body);

		$valid = UserController::validate($input->name, $input->token);

		if (!$valid[0])
		{
			$arrRtn['msg'] = $valid[1];
			$status = 400;
		}
		else
		{
			$arrRtn = [];
			$arrRtn = $valid[1];
			$to = CryptedController::getSupportEmail();

			$email = MailController::doMail($input->subject, $to, $input->email, $input->name, $input->text);

			if ($email):
				$status = 200;
				$arrRtn['data']['message'] = "Email sent successfully.";
			else:
				$status = 405;
				$arrRtn['data']['message'] = "Email not sent.";

			endif;
		}

		return $response->withJSON( $arrRtn )->withStatus($status);
	}

}