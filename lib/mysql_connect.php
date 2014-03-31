<?php

class mysql {
	
	private $c;

	function __construct () {
		$this->c = new mysqli ('', '', '', '');
	}

	/*
	 * Match an item and return result (using eq instead of like)
	 * @param string $item
	 * @param string $table
	 * @param string $column
	 * @return array $nis
	 */
	public function match_item ($item, $table, $column) {
		$query = "SELECT * FROM %s WHERE % = ?";
		$query = sprintf ($query, $this->c->real_escape_string ($table), $this->c->real_escape_string ($column));
		
	}

	/*
	 * Function to return the nis code(s) of a certain gemeente, without extra identifiers (e.g. provincie)
	 * @param string $gemeente
	 * @param string $use_like use like (true) of exact match (false)
	 * @return array $nis [] = array ('nis' => $n, 'gem' => $g, 'prov_n' => $p)
	 */
	public function match_gemeente ($gemeente, $use_like = false) {
		$nis = array ();
		if ($use_like == true) {
			$query = "SELECT g.nis, g.naam, p.nis, g.id, p.id FROM gemeentes g, provincies p WHERE g.naam LIKE CONCAT('%', ?, '%') AND g.prov_id = p.id";
		} else {
			$query = "SELECT g.nis, g.naam, p.nis, g.id, p.id FROM gemeentes g, provincies p WHERE g.naam = ? AND g.prov_id = p.id";
		}
		$st = $this->c->prepare ($query);
		$st->bind_param ('s', $gemeente);
		$st->execute ();
		$st->bind_result ($n, $g, $p, $gid, $pid);
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
	 * @return array $nis [] = array ('dg' => $n, 'gem_n' => $g, 'prov_n' => $p)
	 */
	public function match_deelgemeente ($deelgemeente, $use_like = false) {
		$nis = array ();
		if ($use_like == true) {
			$query = "SELECT d.naam, g.nis, p.nis, d.id, g.id, p.id FROM gemeentes g, provincies p, deelgemeentes d WHERE d.naam LIKE CONCAT('%', ?, '%') AND g.prov_id = p.id AND d.gem_id = g.id";
		} else {
			$query = "SELECT d.naam, g.nis, p.nis, d.id, g.id, p.id FROM gemeentes g, provincies p, deelgemeentes d WHERE d.naam = ? AND g.prov_id = p.id AND d.gem_id = g.id";
		}
		$st = $this->c->prepare ($query);
		$st->bind_param ('s', $deelgemeente);
		$st->execute ();
		$st->bind_result ($n, $g, $p, $did, $gid, $pid);
		while ($st->fetch ()) {
			array_push ($nis, array ('dg' => $n, 'gem_n' => $g, 'prov_n' => $p, 'd_id' => $did, 'g_id' => $gid, 'p_id' => $pid));
		}
		$st->close;
		return $nis;
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
