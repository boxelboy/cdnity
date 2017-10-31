<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Carbon\Carbon;

class TrafficController extends BaseController
{

	public static function totalHistoric($user)
	{
		$records = \ORM::for_table('history')->raw_query('SELECT DATE_FORMAT(date, "%M %Y") as month, sum(traffic) as traffic FROM history WHERE user = :user GROUP BY month ORDER BY date ASC', array('user' => $user))
										 	 ->find_many();

		$arrRtn = [];
		foreach ($records as $record) {
			$hash = [];
			$hash['month'] = $record->month;
			$hash['traffic'] = $record->traffic;
			array_push($arrRtn, $hash);
		}

		return $arrRtn;
	}

	public static function totalByDay($user)
	{
		$start = Carbon::now()->startOfMonth();
		$tomorrow = Carbon::tomorrow();
		$day = $start;

		$arrRtn = [];

		while ($day->lt($tomorrow)) {
			$total = \ORM::for_table('history')->where('user', $user)->where('date', $day)->sum('traffic');
			$arrRtn[$day->toDateString()] = $total;
			$day->addDay(); 
		}

		return $arrRtn;		
	}

	public static function totalByRegion($user)
	{
		$start = Carbon::now()->startOfMonth();

		$arrRtn = [];

		$globalTotal = 0;

		$regions = RegionController::getRegions();

		foreach ($regions as $region) {
			$hash = [];
			$total = \ORM::for_table('history')->where('user', $user)->where('region', $region->code)->where_gte('date', $start)->sum('traffic');
			$arrRtn[$region->code] = $total;
			$globalTotal += $total;
		}

		$total = \ORM::for_table('history')->where('user', $user)->where('region', 'global')->where_gte('date', $start)->sum('traffic');
		$globalTotal += $total;
		$arrRtn['global'] = $globalTotal;

		return $arrRtn;
	}

	public static function getTrafficByRange($id, $start, $end)
	{
		return \ORM::for_table('history')->where('history.user', $id)
										 ->where_gte('date', $start)
										 ->where_lte('date', $end)
										 ->join('userCDN', array('userCDN.id', '=', 'history.userCDN'))
										 ->join('cdn', array('cdn.id', '=', 'userCDN.cdn'))
										 ->order_by_asc('date')->find_many();
	}

	public static function logTraffic($user, $clientcdn, $bytes, $zone)
	{
		$region = '';
		echo "variables passed: ".$user." ".$clientcdn." ".$zone."\n";
		foreach ($bytes as $byte) {
			reset($byte);
			if (key($byte) === 'zone') :
				if ($byte['zone'] === $zone) :
					$traffic = \ORM::for_table('traffic')->create();
					if (array_key_exists('traffic', $byte)):
						$traffic->traffic = (string) $byte['traffic'];
					else:
						$traffic->traffic = '0';
					endif;

					if (array_key_exists('bandwidth', $byte)):
						$traffic->bandwidth = (string) $byte['bandwidth'];
					else:
						$traffic->bandwidth = '0';
					endif;
					$traffic->zone = $byte['zone'];
					$traffic->region = $byte['region'];
					$traffic->user = $user;
					$traffic->userCDN = $clientcdn;
					$traffic->save();
					$region = $byte['region'];

					echo date("Y-m-d H:i:s")." start getrate\n";
					$rate = BillingController::getRate($user, $clientcdn, '2016-12-01', $region);
					$sizes = StaticController::getDataSize();
					echo "rate = ".$rate."\n";
					echo "calculation: ".$rate * ($traffic->bandwidth / $sizes['GB'])."\n";
					BillingController::updTally($user, $rate * ($traffic->bandwidth / $sizes['GB']));
				endif;
			else :

				// this else kept intentionally empty

			endif;
		}

		return 'traffic written to db';
	}

	public function trafficByRegion($request, $response)
	{
		//$input = $request->getParsedBody();
		$body = $request->getBody();
		$input = json_decode($body);

		$valid = UserController::validate($request->getAttribute('name'), $request->getAttribute('token'));

		if (!$valid[0])
		{
			$arrRtn['msg'] = $valid[1];
			$status = 400;
		}
		else
		{
			$arrRtn = $valid[1];
			$zones = ZoneController::getZones($valid[1]['id']);

			foreach ($zones as $zone) {
				$hash = [];
				$hash['name'] = $zone->name;
				$hash['url'] = $zone->url;
				$hash['active'] = $zone->live;
				$hash['zoneID'] = $zone->id;

				$regions = StaticController::getRegions();
				foreach ($regions as $region) {

					$traffic = DashboardController::thisMonthStatsRegion($valid[1]['id'], $region->code);
					$hash[$region->code] = $traffic;
				}
				$traffic = DashboardController::thisMonthStatsRegion($valid[1]['id'], 'global');
				$hash['global'] = $traffic;
				$arrRtn['data']['zones'][$zone->name] = $hash;
			}

			$status = 200;

		}
// change '*' to 'THEDOMAIN'
		return $response->withHeader('Access-Control-Allow-Origin', '*')
						->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
						->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
						->withJSON( $arrRtn )->withStatus($status);
	}

}