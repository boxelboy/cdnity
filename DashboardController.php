<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Carbon\Carbon;

class DashboardController extends BaseController
{
	protected $status;
	protected $start;
	protected $msg;
	protected $log;

	public static function thisMonthStats($user)
	{
		$start = Carbon::now()->startOfMonth();

		$oldStats = \ORM::for_table('history')->where('history.user', $user)
											  ->where_gte('date', $start)
											  ->join('userCDN', array('userCDN.id', '=', 'history.userCDN'))
											  ->find_many();

		$data = [];
		foreach ($oldStats as $key => $stats) {
			$hash = [];
			$hash['date'] = $stats->date;
			$hash['traffic'] = $stats->traffic;
			$hash['bandwidth'] = $stats->bandwidth;
			$hash['zone'] = $stats->zone;
			$hash['region'] = $stats->region;
			array_push($data, $hash);

		}

		return $data;
	}

	public static function thisMonthStatsCDN($user, $cdn)
	{
		$start = Carbon::now()->startOfMonth();
		//$start = '2016-12-14';

		$oldStats = \ORM::for_table('history')->where('history.user', $user)
											  ->where('userCDN', $cdn)
											  ->where_gte('date', $start)
											  ->join('userCDN', array('userCDN.id', '=', 'history.userCDN'))
											  ->find_many();

		$data = [];
		foreach ($oldStats as $key => $stats) {
			$hash = [];
			$hash['date'] = $stats->date;
			$hash['traffic'] = $stats->traffic;
			$hash['bandwidth'] = $stats->bandwidth;
			$hash['zone'] = $stats->zone;
			$hash['region'] = $stats->region;
			array_push($data, $hash);

		}

		return $data;
	}

	public static function thisMonthStatsRegion($user, $region)
	{
		$start = Carbon::now()->startOfMonth();

		$oldStats = \ORM::for_table('history')->where('history.user', $user)
											  ->where('region', $region)
											  ->where_gte('date', $start)
											  ->join('userCDN', array('userCDN.id', '=', 'history.userCDN'))
											  ->find_many();

		$data = [];
		foreach ($oldStats as $key => $stats) {
			$hash = [];
			$hash['date'] = $stats->date;
			$hash['traffic'] = $stats->traffic;
			$hash['bandwidth'] = $stats->bandwidth;
			$hash['zone'] = $stats->zone;
			$hash['cdn'] = $stats->cdn;
			array_push($data, $hash);

		}

		return $data;
	}

	public static function todayStats($request, $response)
	{
		$start = Carbon::now()->toDateString();
		$valid = UserController::validate($request->getAttribute('name'), $request->getAttribute('token'));

		if (!$valid[0])
		{
			$arrRtn['msg'] = $valid[1];
			$status = 400;
		}
		else
		{
			$arrRtn = $valid[1];
			$status = 200;
			$stats = \ORM::for_table('history')->where('history.user', $valid[2]->id)
											   ->where_gte('date', $start)
											   ->join('userCDN', array('userCDN.id', '=', 'history.userCDN'))
											   ->find_many();

			$data = [];
			foreach ($stats as $key => $stat) {
				$hash = [];
				$hash['date'] = $stat->date;
				$hash['cdn'] = $stat->cdn;
				$hash['traffic'] = $stat->traffic;
				$hash['bandwidth'] = $stat->bandwidth;
				$hash['zone'] = $stat->zone;
				$hash['region'] = $stat->region;
				array_push($data, $hash);
			}

			$arrRtn['data'] = $data;
		}

		return $response->withJSON( $arrRtn )->withStatus($status);
	}

	public function oldStats($request, $response)
	{
		$start = Carbon::parse("first day of ".$request->getAttribute('month')." ".$request->getAttribute('year'));
		$end = Carbon::parse("last day of ".$request->getAttribute('month')." ".$request->getAttribute('year'));
		$valid = UserController::validate($request->getAttribute('name'), $request->getAttribute('token'));

		if (!$valid[0])
		{
			$arrRtn['msg'] = $valid[1];
			$status = 400;
		}
		else
		{
			$tokenArr = TokenController::generateToken();

			$valid[2]->token = $tokenArr['key'];
			$valid[2]->token_expire = $tokenArr['expire'];
			$valid[2]->save();

			$oldStats = \ORM::for_table('history')->where('history.user', $valid[2]->id)
												  ->where_gte('history.date', $start)
												  ->where_lte('history.date', $end)
												  ->join('userCDN', array('userCDN.id', '=', 'history.userCDN'))
												  ->find_many();

			$arrRtn['msg'] = "success";
			$arrRtn['token'] = $tokenArr['key'];
			$arrRtn['id'] = $valid[2]->id;
			$arrRtn['name'] = $valid[2]->name;
			$status = 200;

			$data = [];
			foreach ($oldStats as $key => $stats) {
				$hash = [];
				$hash['date'] = $stats->date;
				$hash['cdn'] = $stats->userCDN;
				$hash['traffic'] = $stats->traffic;
				$hash['bandwidth'] = $stats->bandwidth;
				$hash['zone'] = $stats->zone;
				$hash['region'] = $stats->region;
				array_push($data, $hash);

			}
			$arrRtn['data'] =  $data;

		}

		return $response->withJSON( $arrRtn )->withStatus($status);

	}
}