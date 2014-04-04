<?php
include_once ('mysql_connect.php');

class h_apps extends db_connect {
	/*
	 * Fetch an item by ID
	 * @param string $table
	 * @param int $id
	 * @param string $cn column to be returned
	 */
	public function item_by_id ($table, $id, $cn) {
		$query = "SELECT $cn FROM $table t WHERE t.id = ?";
		$st = $this->c->prepare ($query) or die ($this->c->error);
		$st->bind_param ('d', $id);
		$st->execute ();
		$st->bind_result ($r);
		$st->fetch ();
		$st->close;
		return $r;
	}

	/*
	 * Function to return the nis code(s) of a certain gemeente, without extra identifiers (e.g. provincie)
	 * @param string $gemeente
	 * @param string $use_like use like (true) of exact match (false)
	 * @param string $gis use the gis_* tables (false)
	 * @return array $nis [] = array ('nis' => $n, 'gem' => $g, 'prov_n' => $p)
	 */
	public function match_gemeente ($gemeente, $use_like = false, $gis = false) {
		if ($gis == true) {
			$tg = 'gis_gemeentes';
			$tp = 'gis_provincies';
			$pw = null;
			if ($use_like == true) {
				$query = "SELECT g.nis, g.naam, g.id FROM $tg g WHERE g.naam LIKE CONCAT('%', ?, '%')";
			} else {
				$query = "SELECT g.nis, g.naam, g.id FROM $tg g WHERE g.naam = ?";
			}
		} else {
			$tg = 'gemeentes';
			$tp = 'provincies';
			$pw = 'AND g.prov_id = p.id';
			if ($use_like == true) {
				$query = "SELECT g.nis, g.naam, p.nis, g.id, p.id FROM $tg g, $tp p WHERE g.naam LIKE CONCAT('%', ?, '%') $pw";
			} else {
				$query = "SELECT g.nis, g.naam, p.nis, g.id, p.id FROM $tg g, $tp p WHERE g.naam = ? $pw";
			}
		}
		$nis = array ();
		$st = $this->c->prepare ($query) or die ($this->c->error);
		$st->bind_param ('s', $gemeente);
		$st->execute ();
		if ($gis == true) {
			$st->bind_result ($n, $g, $gid);
		} else {
			$st->bind_result ($n, $g, $p, $gid, $pid);
		}
		while ($st->fetch ()) {
			array_push ($nis, array ('nis' => $n, 'gem' => $g, 'prov_n' => $p, 'g_id' => $gid, 'p_id' => $pid));
		}
		$st->close;
		return $nis;
	}

	/*
	 * Function to return the nis code(s) of a certain deelgemeente, with NIS of the master-gemeente and prov
	 * @param string $deelgemeente
	 * @param string $use_like
	 * @param string $gis use the gis_* tables (false)
	 * @return array $nis [] = array ('dg' => $n, 'gem_n' => $g, 'prov_n' => $p)
	 */
	public function match_deelgemeente ($deelgemeente, $use_like = false, $gis = false) {
		if ($gis == true) {
			$tg = 'gis_gemeentes';
			$tp = 'gis_provincies';
			$td = 'gis_deelgemeentes';
			$pw = null;
			$gw = 'gemeente_id';
			if ($use_like == true) {
				$query = "SELECT d.naam, g.nis, d.id, g.id FROM $tg g, $td d WHERE d.naam LIKE CONCAT('%', ?, '%') AND d.$gw = g.id";
			} else {
				$query = "SELECT d.naam, g.nis,d.id, g.id FROM $tg g, $td d WHERE d.naam = ? AND d.$gw = g.id";
			}
		} else {
			$tg = 'gemeentes';
			
			$tp = 'provincies';
			$td = 'deelgemeentes';
			$pw = 'AND g.prov_id = p.id';
			$gw = 'gem_id';
			if ($use_like == true) {
				$query = "SELECT d.naam, g.nis, p.nis, d.id, g.id, p.id FROM $tg g, $tp p, $td d WHERE d.naam LIKE CONCAT('%', ?, '%') $pw AND d.$gw = g.id";
			} else {
				$query = "SELECT d.naam, g.nis, p.nis, d.id, g.id, p.id FROM $tg g, $tp p, $td d WHERE d.naam = ? $pw AND d.$gw = g.id";
			}
		}
		$nis = array ();
		
		$st = $this->c->prepare ($query) or die ($this->c->error);
		$st->bind_param ('s', $deelgemeente);
		$st->execute ();
		if ($gis == true) {
			$st->bind_result ($n, $g, $did, $gid);
		} else {
			$st->bind_result ($n, $g, $p, $did, $gid, $pid);
		}
		while ($st->fetch ()) {
			array_push ($nis, array ('dg' => $n, 'gem_n' => $g, 'prov_n' => $p, 'd_id' => $did, 'g_id' => $gid, 'p_id' => $pid));
		}
		$st->close;
		return $nis;
	}

	/*
	 * Function to return the following information pertaining to a particular street
	 *	id, name, deelgemeente.id, deelgemeente.name, gemeente.id, gemeente.name
	 * @param string $straat
	 * @param string $use_like
	 * @param string $gis use the gis_* tables (false)
	 * @return array $results[] = array (id =>, n =>, did =>, dn =>, gid =>, gn =>)
	 */
	public function match_straat ($straat, $use_like = false, $gis = false) {
		if ($gis == true) {
			$tg = 'gis_gemeentes';
			$td = 'gis_deelgemeentes';
			$ts = 'gis_straten';
		} else {
			$tg = 'gemeentes';
			$td = 'deelgemeentes';
			$ts = 'straten';
		}
		$nis = array ();
		if ($use_like == true) {
			$query = "SELECT s.id, s.naam, g.naam, g.id, d.naam, d.id FROM $ts s, $tg g, $td d WHERE s.dg_id = d.id AND d.gemeente_id = g.id AND s.naam LIKE CONCAT('%%', '%s', '%%')";
		} else {
			$query = "SELECT s.id, s.naam, g.naam, g.id, d.naam, d.id FROM $ts s, $tg g, $td d WHERE s.dg_id = d.id AND d.gemeente_id = g.id AND s.naam = '%s'";
		}
		$query = sprintf ($query, $this->c->real_escape_string ($straat));
		$r = $this->c->query ($query) or die ($this->c->error);
		while ($row = $r->fetch_array ()) {
			array_push ($nis, array (
				'id' => $row[0],
				'n' => $row[1],
				'did' => $row[5],
				'dn' => $row[4],
				'gid' => $row[3],
				'gn' => $row[2]));
		}
		/*
		$st = $this->c->prepare ($query);
		$st->bind_param ('s', $straat);
		$st->execute ();
		$st->bind_result ($sid, $sn, $gn, $gid, $dn, $did);
		while ($st->fetch ()) {
			array_push ($nis, array ('id' => $sid, 'n' => $sn, 'did' => $did, 'dn' => $dn, 'gid' => $gid, 'gn' => $gn));
		}
		$st->close ();*/
		return $nis;
	}

	/*
	 * Function to return the following information about straten by deelgemeente_id
	 *	id
	 *	name
	 *	wgs84_lat & long
	 * @param int $dg_id
	 * @return array $results[i] = array (id =>, name =>, wgs84_lat =>, wgs84_long =>, dg_id =>)
	 */
	public function straten_by_deelgemeente ($dg_id) {
		$result = array ();
		$query = "SELECT s.id, s.naam, s.wgs84_lat, s.wgs84_long FROM gis_straten s, gis_deelgemeentes d WHERE d.id = s.dg_id AND d.id = ?";
		$st = $this->c->prepare ($query);
		$st->bind_param ('d', $dg_id);
		$st->execute ();
		$st->bind_result ($sid, $sn, $slat, $slong);
		while ($st->fetch ()) {
			array_push ($result, array ('id' => $sid, 'name' => $sn, 'wgs84_lat' => $slat, 'wgs84_long' => $slong, 'dg_id' => $dg_id));
		}
		$st->close ();
		return $result;
	}

	/*
	 * Function to return the following information about deelgemeentes by gemeente_id
	 * id
	 * name
	 * wgs84_lat & long
	 * @param int $g_id
	 * return array $results[i] = array (id =>, name =>, wgs84_lat =>, wgs84_long =>, g_id =>)
	 */
	public function deelgemeentes_by_gemeente ($g_id) {
		$result = array ();
		$query = "SELECT d.id, d.naam, d.wgs84_lat, d.wgs84_long FROM gis_deelgemeentes d, gis_gemeentes g WHERE g.id = d.gemeente_id AND g.id = ?";
		$st = $this->c->prepare ($query);
		$st->bind_param ('d', $g_id);
		$st->execute ();
		$st->bind_result ($did, $dn, $dlat, $dlong);
		while ($st->fetch ()) {
			array_push ($result, array ('id' => $did, 'name' => $dn, 'wgs84_lat' => $dlat, 'wgs84_long' => $dlong, 'g_id' => $g_id));
		}
		$st->close ();
		return $result;
	}

	/*
	 * Function to return the information held in the DB about a monument
	 * @param string $monument
	 * @param bool $use_like
	 * @param optional $pid, $gid, $did ids (DB) of the province etc. to query (! this works hierarchical, e.g. if deelgemeente is set, gemeente & provincie must be set!)
	 * @return result[] = array ('naam' => $naam, 'url' => $url, 'adres' => array ('straat', 'nummer', 'dg', 'gem', 'prov', 'wgs84_lat', 'wgs84_long'))
	 */
	public function match_monument ($monument, $use_like = false, $pid = null, $gid = null, $did = null) {
		$monument = $this->c->real_escape_string ($monument);
		$result = array ();
		if ($use_like == true) {
			$query = "SELECT r.naam, r.url, a.huisnummer, a.wgs84_lat, a.wgs84_long, s.naam, d.naam, g.naam, p.naam
			FROM relicten r, adres a, straten s, deelgemeentes d, gemeentes g, provincies p
			WHERE
			%s
			r.adres_id = a.id AND
			a.str_id = s.id AND
			a.gem_id = g.id AND
			a.deelgem_id = d.id AND
			a.prov_id = p.id AND
			r.naam LIKE CONCAT('%%', '%s', '%%')";
		} else {
			$query = "SELECT r.naam, r.url, a.huisnummer, a.wgs84_lat, a.wgs84_long, s.naam, d.naam, g.naam, p.naam
			FROM relicten r, adres a, straten s, deelgemeentes d, gemeentes g, provincies p
			WHERE
			%s
			r.adres_id = a.id AND
			a.str_id = s.id AND
			a.gem_id = g.id AND
			a.deelgem_id = d.id AND
			a.prov_id = p.id AND
			r.naam = '%s'";
		}
		if ($pid || $gid || $did) {
			$pid = $this->c->real_escape_string ($pid);
			if ($did) {
				$did = $this->c->real_escape_string ($did);
				$gid = $this->c->real_escape_string ($gid);
				$query = sprintf ($query, 'p.id = '.$pid.' AND g.id = '.$gid.' AND d.id = '.$did.' AND
', $monument);
				/*$st = $this->c->prepare ($query);
				$st->bind_param ('sddd', $monument, $pid, $gid, $did);*/
			} elseif ($gid) {
				$gid = $this->c->real_escape_string ($gid);
				$query = sprintf ($query, 'p.id = '.$pid.' AND g.id = '.$gid.' AND
', $monument);
				/*$st = $this->c->prepare ($query);
				$st->bind_param ('sdd', $monument, $pid, $gid);*/
			} else {
				$query = sprintf ($query, 'p.id = '.$pid.' AND
', $monument);
				/*$st = $this->c->prepare ($query);
				$st->bind_param ('sd', $monument, $pid);*/
			}
		} else {
			$query = sprintf ($query, ' ', $monument);
			/*$st = $this->c->prepare ($query);
			$st->bind_param ('s', $monument);*/
		}
		$r = $this->c->query ($query) or die ($this->c->error);
		while ($row = $r->fetch_array ()) {
			array_push ($result, array ('naam' => $row[0], 'url' => $row[1], 'adres' => array (
				'straat' => $row[5],
				'nummer' => $row[2],
				'deelgem' => $row[6],
				'gem' => $row[7],
				'prov' => $row[8],
				'wgs84_lat' => $row[3],
				'wgs84_long' => $row[4])));
		}
		return $result;
		/* For some reason, below refuses to work 
		$st->execute ();
		$st->bind_result ($rn, $ru, $ah, $awa, $awo, $sn, $dn, $gn, $pn);
		while ($st->fetch ()) {
			array_push ($result, array ('naam' => $rn, 'url' => $ru, 'adres' => array (
				'straat' => $sn,
				'nummer' => $ah,
				'deelgem' => $dn,
				'gem' => $gn,
				'prov' => $pn,
				'wgs84_lat' => $awa,
				'wgs84_long' => $awo)));
		}
		$st->close;
		return $result;*/
	}
}
?>