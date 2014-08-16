<?php
include_once ('mysql_connect.php');
/**/

class h_apps extends db_connect {
	/*
	 * Fetch an item by ID
	 * @param string $table
	 * @param int $id
	 * @param string $cn column to be returned
	 */
	protected function item_by_id ($table, $id, $cn) {
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
	 * Function to eliminate duplicates in result sets of monumenten
	 * @param array $input[x] = array (y)
	 * @return array $output[$key] = array (y)
	 */
	protected function eliminate_duplicates_monuments ($input) {
		$output = array ();
		foreach ($input as $result) {
			/* relict_id is the key in $output, see if it exists */
			if (isset ($output[$result['relict_id']])) {
				$output[$result['relict_id']]['adres'] = array_merge ($output[$result['relict_id']]['adres'], $result['adres']);
			} else {
				/* Add */
				$output[$result['relict_id']] = $result;
			}
		}
		/* Merge result adres */
		/*
		 * output as result
		 * 	result[adres] = o_adres
		 * 	$result[adres][adres誰d]*/
		foreach ($output as $relict_id => $result) {
			$old_adres = $result['adres'];
			$result['adres'] = array ();
			foreach ($old_adres as $adres) {
				$result['adres'][$adres['id']] = $adres;
			}
			$output[$relict_id] = $result;
		}
		return $output;
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
			$pw = 'AND g.provincie_id = p.id';
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
			$pw = 'AND g.provincie_id = p.id';
			$gw = 'gemeente_id';
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
		$r = $this->c->query ($query, MYSQLI_USE_RESULT) or die ($this->c->error);
		while ($row = $r->fetch_array ()) {
			array_push ($nis, array (
				'id' => $row[0],
				'n' => $row[1],
				'did' => $row[5],
				'dn' => $row[4],
				'gid' => $row[3],
				'gn' => $row[2]));
		}
		$r->free ();
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
			array_push ($result, array ('id' => $did, 'deelgemeente' => $dn, 'name' => $dn, 'wgs84_lat' => $dlat, 'wgs84_long' => $dlong, 'g_id' => $g_id));
		}
		$st->close ();
		return $result;
	}

	/*
	 * Function to fetch a monument from the db by relict_id
	 * @param string $relict_id
	 * @return array $monument = 'naam' => $naam, 'url' => $url, 'relict_id' => $relict_id, 'adres'[i] => array ('straat', 'nummer', 'dg', 'gem', 'prov', 'wgs84_lat', 'wgs84_long')
	 */
	public function get_monument_by_r_id ($relict_id) {
		$monument = array (
			'id' => array (), /* TODO: change this, there should only be one ID (dirty DB) */
			'naam' => '',
			'url' => '',
			'relict_id' => '',
			'adres' => array ()
			);
		/*
		 * Fetch the monument
		 */
		$q = "SELECT DISTINCT r.id, r.naam, r.url, r.relict_id FROM relicten r WHERE r.relict_id = ?";
		$st = $this->c->prepare ($q);
		$st->bind_param ('s', $relict_id);
		$st->execute ();
		$st->bind_result ($id, $naam, $url, $relict_id);
		while ($st->fetch ()) {
			array_push ($monument['id'], $id);
			$monument['naam'] = $naam;
			$monument['url'] = $url;
			$monument['relict_id'] = $relict_id;
		}
		$st->close ();
		$st = null;
		/*
		 * Fetch the addresses
		 */
		foreach ($monument['id'] as $m_id) {
			$monument['adres'] = $this->get_address_by_r_id ($m_id);
		}
		return $monument;
	}

	/*
	 * Function to fetch an address by relict.relict_id
	 * @param string $relict_id
	 * @return array $address[i] = array ('straat', 'nummer', 'deelgemeente', 'gemeente', 'provincie', 'wgs84_lat', 'wgs84_long')
	 */
	public function get_address_by_relict_id ($relict_id) {
		$address = array ();
		$q = "SELECT DISTINCT s.naam, d.naam, g.naam, p.naam, h.naam, a.wgs84_lat, a.wgs84_long, a.id, d.id, g.id, p.id, s.id, h.id FROM adres a, straten s, deelgemeentes d, gemeentes g, provincies p, link l, huisnummers h, relicten r
		WHERE
		a.str_id = s.id AND
		a.gem_id = g.id AND
		a.deelgem_id = d.id AND
		a.prov_id = p.id AND
		a.huisnummer_id = h.id AND
		l.ID_link_a = a.id AND
		l.ID_link_r = r.id AND
		r.relict_id = ?";
		$st = $this->c->prepare ($q);
		$st->bind_param ('s', $relict_id);
		$st->execute ();
		$st->bind_result ($straat, $deelgemeente, $gemeente, $provincie, $huisnummer, $wgs84_lat, $wgs84_long, $a_id, $d_id, $g_id, $p_id, $s_id, $h_id);
		while ($st->fetch ()) {
			$a = array (
				'straat' => $straat,
				'deelgemeente' => $deelgemeente,
				'gemeente' => $gemeente,
				'provincie' => $provincie,
				'huisnummer' => $huisnummer,
				'wgs84_lat' => $wgs84_lat,
				'wgs84_long' => $wgs84_long,
				'id' => $a_id,
				'huisnummer_id' => $h_id,
				'straat_id' => $s_id,
				'deelgemeente_id' => $d_id,
				'gemeente_id' => $g_id,
				'provincie_id' => $p_id
			);
			array_push ($address, $a);
		}
		$st->close ();
		$st = null;
		return $address;
	}
	
	/*
	 * Function to fetch an address by relict.id (!= relict_id !)
	 * @param int $id
	 * @return $address[i] = array ('straat', 'nummer', 'deelgemeente', 'gemeente', 'provincie', 'wgs84_lat', 'wgs84_long')
	 */
	public function get_address_by_r_id ($r_id) {
		$address = array ();
		$q = "SELECT DISTINCT s.naam, d.naam, g.naam, p.naam, h.naam, a.wgs84_lat, a.wgs84_long, a.id, d.id, g.id, p.id, s.id, h.id FROM adres a, straten s, deelgemeentes d, gemeentes g, provincies p, link l, huisnummers h
		WHERE
		a.str_id = s.id AND
		a.gem_id = g.id AND
		a.deelgem_id = d.id AND
		a.prov_id = p.id AND
		l.ID_link_a = a.id AND
		a.huisnummer_id = h.id AND
		l.ID_link_r = ?";
		$st = $this->c->prepare ($q);
		$st->bind_param ('s', $r_id);
		$st->execute ();
		$st->bind_result ($straat, $deelgemeente, $gemeente, $provincie, $huisnummer, $wgs84_lat, $wgs84_long, $a_id, $d_id, $g_id, $p_id, $s_id, $h_id);
		while ($st->fetch ()) {
			$a = array (
				'straat' => $straat,
				'deelgemeente' => $deelgemeente,
				'gemeente' => $gemeente,
				'provincie' => $provincie,
				'huisnummer' => $huisnummer,
				'wgs84_lat' => $wgs84_lat,
				'wgs84_long' => $wgs84_long,
				'id' => $a_id,
				'huisnummer_id' => $h_id,
				'straat_id' => $s_id,
				'deelgemeente_id' => $d_id,
				'gemeente_id' => $g_id,
				'provincie_id' => $p_id
			);
			array_push ($address, $a);
		}
		$st->close ();
		$st = null;
		return $address;
	}

	/*
	 * Function to fetch an address by adres.id (!= adres_id!)
	 */
	/*
	 * Function to fetch a monument from the db by id
	
	 * @param string $id
	 * @return array $monument = 'naam' => $naam, 'url' => $url, 'relict_id' => $relict_id, 'adres'[i] => array ('straat', 'nummer', 'dg', 'gem', 'prov', 'wgs84_lat', 'wgs84_long')
	 */
	public function get_monument_by_id ($id) {
		$monument = array (
			'id' => array (), /* TODO: change this, there should only be one ID (dirty DB) */
			'naam' => '',
			'url' => '',
			'relict_id' => '',
			'adres' => array ()
			);
		/*
		 * Fetch the monument
		 */
		$q = "SELECT DISTINCT r.id, r.naam, r.url, r.relict_id FROM relicten r WHERE r.id = ?";
		$st = $this->c->prepare ($q);
		$st->bind_param ('s', $id);
		$st->execute ();
		$st->bind_result ($id, $naam, $url, $relict_id);
		while ($st->fetch ()) {
			array_push ($monument['id'], $id);
			$monument['naam'] = $naam;
			$monument['url'] = $url;
			$monument['relict_id'] = $relict_id;
		}
		$st->close ();
		$st = null;
		/*
		 * Fetch the addresses
		 */
		/*foreach ($monument['id'] as $m_id) {
			$monument['adres'] = $this->get_address_by_r_id ($m_id);
		}  ## It should search for the addresses by relict_id; otherwise it will find only one address for a certain id (dirty tables) */
		$monument['adres'] = $this->get_address_by_relict_id ($monument['relict_id']);
		return $monument;
	}

	/*
	 * Function to return the information held in the DB about a monument
	 * @param string $monument
	 * @param bool $use_like
	 * @param optional $pid, $gid, $did ids (DB) of the province etc. to query (! this works hierarchical, e.g. if deelgemeente is set, gemeente & provincie must be set!)
	 * @return result[] = array ('naam' => $naam, 'url' => $url, 'adres' => array ('straat', 'nummer', 'dg', 'gem', 'prov', 'wgs84_lat', 'wgs84_long'))
	 */
	/*
	 * Currently wrapped
	 */
	public function match_monument ($monument, $use_like = false, $pid = null, $gid = null, $did = null) {
		$ids = $this->query_relicten ($monument, $pid, $gid, $did);
	//	print_r ($ids);
		$results = $this->list_relicten ($ids, 'ALL');
		/* Eliminate duplicates */
		$results = $this->eliminate_duplicates_monuments ($results);
	//	print_r ($results);
	//	exit (0);
		return $results;
	}

	/*
	 * Returns a list of relicten with address information and all
	 * @param $id_list (from query_relicten)
	 * @param int $limit - amount of items to return
	 * @param int $start = null starting element
	 * @return $results
	 */
	public function list_relicten ($id_list, $limit, $start = null) {
		$results = array ();
		if ($start == null) {
			$start = 0;
		}
		if ($limit == 'ALL') {
			$get_list = array_slice ($id_list['ids'], $start);
		} else {
			$get_list = array_slice ($id_list['ids'], $start, $limit);
		}
		foreach ($get_list as $id) {
			array_push ($results, $this->get_monument_by_id ($id));
		}
		return $results;
	}

	/*
	 * Returns a list of IDs corresponding to a particular query.
	 * $did, $gid, $pid is hierarchical: $did is most specific, $gid less, $pid least => if we have did, we don't check gid or pid (etc.)
	 */
	public function query_relicten ($monument, $pid = null, $gid = null, $did = null) {
		$result = array ();
		$monument = $this->c->real_escape_string ($monument);
		/*
		 * Get all relicten that correspond to this query
		 */
		$q = "SELECT r.id FROM relicten r, link l, adres a WHERE r.naam LIKE CONCAT ('%%', ?, '%%') AND l.ID_link_a = a.id AND l.ID_link_r = r.id%s";
		if ($did) {
			$q = sprintf ($q, "\nAND a.deelgem_id = ?");
			$st = $this->c->prepare ($q);
			$st->bind_param ('sd', $monument, $did);
		} elseif ($gid) {
			$q = sprintf ($q, "\nAND a.gem_id = ?");
			$st = $this->c->prepare ($q);
			$st->bind_param ('sd', $monument, $gid);
		} elseif ($pid) {
			$q = sprintf ($q, "\nAND a.prov_id = ?");
			$st = $this->c->prepare ($q);
			$st->bind_param ('sd', $monument, $pid);
		} else {
			$q = sprintf ($q, "");
			$st = $this->c->prepare ($q);
			$st->bind_param ('s', $monument);
		}
		$st->execute ();
		$st->bind_result ($r_id);
		$ids = array ();
		while ($st->fetch ()) {
			array_push ($ids, $r_id);
		}
		$st->close ();
		$st = null;
		$result['total'] = count ($ids);
		$result['ids'] = $ids;
		return $result;
		
		 /*
		$q = "SELECT COUNT (r.relict_id) FROM relicten r, adres a, straten s, deelgemeentes d, gemeentes g, provincies p
			WHERE
			%s
			r.adres_id = a.id AND
			a.str_id = s.id AND
			a.gem_id = g.id AND
			a.deelgem_id = d.id AND
			a.prov_id = p.id AND
			r.naam LIKE CONCAT('%%', '%s', '%%')";
		if ($limit == 'ALL') {
		}
		$result = array ();
		if ($use_like == true) {
			$query = "SELECT r.naam, r.url, a.huisnummer, a.wgs84_lat, a.wgs84_long, s.naam, d.naam, g.naam, p.naam, r.relict_id
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
			$query = "SELECT r.naam, r.url, a.huisnummer, a.wgs84_lat, a.wgs84_long, s.naam, d.naam, g.naam, p.naam, r.relict_id
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
				$st->bind_param ('sddd', $monument, $pid, $gid, $did);*//*
			} elseif ($gid) {
				$gid = $this->c->real_escape_string ($gid);
				$query = sprintf ($query, 'p.id = '.$pid.' AND g.id = '.$gid.' AND
', $monument);
				/*$st = $this->c->prepare ($query);
				$st->bind_param ('sdd', $monument, $pid, $gid);*//*
			} else {
				$query = sprintf ($query, 'p.id = '.$pid.' AND
', $monument);
				/*$st = $this->c->prepare ($query);
				$st->bind_param ('sd', $monument, $pid);*//*
			}
		} else {
			$query = sprintf ($query, ' ', $monument);
			/*$st = $this->c->prepare ($query);
			$st->bind_param ('s', $monument);*//*
		}
		$r = $this->c->query ($query, MYSQLI_USE_RESULT) or die ($this->c->error);
		while ($row = $r->fetch_array ()) {
			$relict_id = $row[9];
			##
			# See bug #5
			##
			if (isset ($result[$relict_id])) {
				array_push ($result[$relict_id]['adres'], array (
					'straat' => $row[5],
					'nummer' => $row[2],
					'deelgem' => $row[6],
					'gem' => $row[7],
					'prov' => $row[8],
					'wgs84_lat' => $row[3],
					'wgs84_long' => $row[4]
				));
			} else {
				$result[$relict_id] = array ('naam' => $row[0], 'url' => $row[1], 'adres' => array (
					array (
						'straat' => $row[5],
						'nummer' => $row[2],
						'deelgem' => $row[6],
						'gem' => $row[7],
						'prov' => $row[8],
						'wgs84_lat' => $row[3],
						'wgs84_long' => $row[4]
						)
				));
			}
		}
		$r->free ();
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

	/*
	 * Function to get all gemeentes from gis_gemeentes
	 * @return array $gemeentes
	 */
	public function get_all_gemeentes () {
		$gemeentes = array ();
		$q = "SELECT g.id, g.naam FROM gis_gemeentes g";
		$st = $this->c->prepare ($q);
		$st->execute ();
		$st->bind_result ($id, $naam);
		while ($st->fetch ()) {
			array_push ($gemeentes, array ('id' => $id, 'gemeente' => $naam));
		}
		$st->close ();
		$st = null;
		return $gemeentes;
	}
}
?>
