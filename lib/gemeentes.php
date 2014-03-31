<?php

include_once ('nlp.php');

class glp extends nlp {

	/* parts[1 - 2 - 3] => 2 = deelgemeente | 3 = gemeente */
	/*
	 * Sentence can be of the following forms:
	 * straat in deelgemeente, gemeente
	 * straat in gemeente
	 * straat in deelgemeente
	 * deelgemeente
	 * gemeente
	 * straat
	 */
	protected function split_sentence ($sentence) {
		$dc = parent::split_sentence ($sentence);
		if (preg_match ('/^(.*), ?(.*)$/', $sentence, $m) && count ($dc) != 4) {
			/* We can have sentences with only comma's */
			$dc = $m;
			$dc['CFLAG'] = true;
		}
		return $dc;
	}

	/*
	 * Function to return all possible gemeentes that are LIKE a given one
	 * @param string $gn gemeente naam
	 * @return array $gs[0] = array (id =>, n => )
	 */
	protected function gis_gemeente ($gn) {
		$gs = array ();
		$gr = $this->m->match_gemeente ($gn, true, true);
		foreach ($gr as $r) {
			array_push ($gs, array ('id' => $r['g_id'], 'n' => $r['gem']));
		}
		return $gs;
	}

	/*
	 * Function to return all possible deelgemeentes that are LIKE a given one
	 * @param string $dn deelgemeente name
	 * @param int $gid id of the gemeente (optional)
	 * @return array $ds[0] = array (id =>, n => , gid =>)
	 */
	protected function gis_deelgemeente ($dn, $gid = null) {
		$ds = array ();
		$dr = $this->m->match_deelgemeente ($dn, true, true);
		foreach ($dr as $r) {
			if ($gid == $r['g_id'] || $gid = null) {
				array_push ($ds, array ('id' => $r['d_id'], 'n' => $r['dg'], 'gid' => $r['g_id']));
			}
		}
		return $ds;
	}

	/*
	 * Function to return all possible streets that are LIKE a given one
	 * @param string $sn street name
	 * @param int $did id of the deelgemeente (optional)
	 * @return array $ss[0] = array (id =>, n =>, dgn =>, did =>, gn =>, gid =>)
	 */
	protected function gis_straat ($sn, $did = null) {
		$ss = array ();
		$sr = $this->m->match_straat ($sn, true, true);
		foreach ($sr as $r) {
			if ($did == $r['did'] || $did = null) {
				array_push ($ss, array ('id' => $r['id'], 'n' => $r['n'], 'did' => $r['did'], 'dgn' => $r['dn'], 'gn' => $r['gn'], 'gid' => $r['gid']));
			}
		}
		return $ss;
	}
	
	/*
	 * Function to query the full sentence (x in y, z)
	 * @param array $p parts from $this->split_sentence
	 * @return array $results[i] = array (q => , g =>, dg =>, s =>)
	 */
	protected function full_string ($p) {
		$results = array ();
		$gs = $this->gis_gemeente ($p[3]);
		foreach ($gs as $g) {
			$dgs = $this->gis_deelgemeente ($p[2], $g['id']);
			foreach ($dgs as $dg) {
				$ss = $this->gis_straat ($p[1], $dg['id']);
				foreach ($ss as $s) {
					array_push ($results, array ('q' => $p[1], 'g' => $s['gn'], 'dg' => $s['dgn'], 's' => $s['n'], 'sid' => $s['id'], 'did' => $s['did'], 'gid' => $s['gid']));
				}
			}
		}
		return $results;
	}
	
	/*
	 * Function to search for a $sentence
	 * @param string $sentence
	 * Sentence can be of the following forms:
	 * straat in deelgemeente, gemeente
	 * straat in gemeente
	 * straat in deelgemeente
	 * deelgemeente, gemeente
	 * deelgemeente
	 * gemeente
	 * straat
	 * @return array $items
	 */
	public function gemeente ($sentence) {
		/* Split in 1+ parts */
		$items = array ();
		$parts = $this->split_sentence ($sentence);
		/* If count ($parts) == 4, then it is of the form straat in deelgemeente, gemeente */
		if (count ($parts) == 4) {
			$items = $this->full_string ($parts);
			return $items;
		}
		/* Any other form is a problem */
		if (isset ($parts['CFLAG']) && $parts['CFLAG'] == true) {
			/* Sentece contains no 'in', only a comma */
			$dgs = $this->gis_deelgemeente ($parts[1]);
			foreach ($dgs as $dg) {
				array_push ($items, array ('q' => $parts[0], 'g' => $this->m->item_by_id ('gemeentes', $dg['g_id'], 'naam'), 'dg' => $dg['n'], 's' => $this->m->straten_by_deelgemeente ($dg['id']));
			}
		}
		/* All other cases are like:
			parts[1] is straat? => lookup straat
			YES
			parts[2] is gemeente OR deelgemeente: compare
			NO
			parts[1] is deelgemeente? => lookup deelgemeente
			NO
			parts[1] is gemeente? => lookup gemeente
			*/
		return $items;
	}
}

?>