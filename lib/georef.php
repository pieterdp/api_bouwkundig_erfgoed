<?php
/*
 * http://www.uwgb.edu/dutchs/UsefulData/UTMFormulas.htm
 * http://www.codecodex.com/wiki/Calculate_Distance_Between_Two_Points_on_a_Globe#PHP <= in KM
 * Must do the following:
 *		m -> ° (wgs84) (distance)
 *		° -> m (wgs84) (distance)
 *		draw a square with r = x m around a given point (°)
 */

class georef extends h_apps {

	protected $earth_r;
	protected $adjacent;

	function __construct () {
		$this->earth_r = 6371;
		$this->adjacent = 10;
	}

	public function distance_d ($lat1, $long1, $lat2, $long2) {
		$rlat = deg2rad ($lat2 - $lat1);
		$rlon = deg2rad ($long2 - $long1);
		$a = sin ($rlat/2) * sin ($rlat/2) + cos (deg2rad ($lat1)) * cos (deg2rad ($lat2)) * sin ($rlon/2) * sin ($rlon/2);
		$c = 2 * asin (sqrt ($a));
		$d = $this->earth_r * $c;
		return $d;
	}

	public function get_adjacent_monuments ($lat, $long) {
		/*
		 * 111319.9 m = 1° x cos (lat) => diff of 10 m
		 */
		 $diffdeg = ($this->adjacent * (1/111319.9)) / cos ($lat);
		 $points = array (
			'p1' => array ('lat' => $lat + $diffdeg, 'long' => $long),
			'p2' => array ('lat' => $lat - $diffdeg, 'long' => $long),
			'p3' => array ('lat' => $lat, 'long' => $long + $diffdeg),
			'p4' => array ('lat' => $lat, 'long' => $long - $diffdeg)
		 );
		$query = "SELECT r.id FROM relicten r, adres a, link l WHERE
				a.wgs84_lat BETWEEN CAST (? AS DECIMAL) AND CAST (? AS DECIMAL) AND
				a.wgs84_long BETWEEN CAST (? AS DECIMAL) AND CAST (? AS DECIMAL) AND
				a.id = l.ID_link_a AND
				r.id = l.ID_link_r;";
	}
	/*1/111319,9 diff*/
}
?>