<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class TokenController extends BaseController
{
	public static function validateToken($name, $token)
	{
		$bod = \ORM::for_table('user')->where(array(
										'name' => $name,
										'token' => $token
										))->find_one();
		if (!$bod):
			return "invalid token";
		elseif (strtotime($bod->token_expire) < strtotime(date('Y-m-d H:i:s'))):
			return "timeout";
		else:
			return $bod;
		endif;
	}

	public static function generateToken()
	{
		return array('key' => bin2hex(openssl_random_pseudo_bytes(16)), 'expire' => date('Y-m-d H:i:s', strtotime('+30 minutes')));
	}

	public static function generateSMSToken()
	{
		return array('key' => rand(100000, 999999), 'expire' => date('Y-m-d H:i:s', strtotime('+30 minutes')));
	}
}