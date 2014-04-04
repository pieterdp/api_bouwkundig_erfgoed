<?php

include_once ('h_apps.php');

class nlp {

	protected $m;

	function __construct () {
		$this->m = new h_apps ();
	}

	/*
	 * Function to split a string of the form
	 * <monument/typologie> in <deelgemeente>, <gemeente>
	 * @param string $sentence
	 * @return array $splitted_string
	 */
	protected function split_sentence ($sentence) {
		preg_match ('/^(.*) in (.*)$/i', $sentence, $dc);
		if (preg_match ('/^(.*), ?(.*)$/', $dc[2], $m) === 1) {
			$dc[2] = $m[1];
			$dc[3] = $m[2];
		}
		return $dc;
	}

	/*
	 * Function to determine gemeentes & deelgemeentes in $sentence
	 * @param array $parts - parts of the split string
	 * @return array $results
	 */
	protected function parse_loc ($parts) {
		$results = array ();
		if (isset ($parts[3])) {
			/* $parts[3] contains the gemeente */
			$g_nis = $this->m->match_gemeente ($parts[3], false);
			foreach ($g_nis as $item) {
				array_push ($results, array ('q' => $parts[1], 'prov_n' => $item['prov_n'], 'gem_n' => $item['nis'], 'dg' => $parts[2], 'pid' => $item['p_id'], 'gid' => $item['g_id'], 'did' => $item['d_id']));
			}
		} else {
			/* Query the database whether a gemeente with this name exists */
			$g_nis = $this->m->match_gemeente ($parts[2], false);
			if (empty ($g_nis)) {
				/* No gemeente with this name exists, query deelgemeentes */
				$d_nis = $this->m->match_deelgemeente ($parts[2], false);
				if (empty ($d_nis)) {
					/* No deelgemeente exists, so only string */
					array_push ($results, array ('q' => $parts[1], 'prov_n' => '', 'gem_n' => '', 'dg' => ''));
				} else {
					foreach ($d_nis as $item) {
						array_push ($results, array ('q' => $parts[1], 'prov_n' => $item['prov_n'], 'gem_n' => $item['gem_n'], 'dg' => $item['dg'], 'pid' => $item['p_id'], 'gid' => $item['g_id'], 'did' => $item['d_id']));
					}
				}
			} else {
				foreach ($g_nis as $item) {
					array_push ($results, array ('q' => $parts[1], 'prov_n' => $item['prov_n'], 'gem_n' => $item['nis'], 'dg' => '', 'pid' => $item['p_id'], 'gid' => $item['g_id']));
				}
			}
		}
		return $results;
	}

	/*
	 * Function to interpret a search string of the form
	 * <monument> in <deelgemeente>, <gemeente>
	 * to interface with the inventaris onroerend erfgoed.
	 * <gemeente> or <deelgemeente> is optional. If only one
	 * of them is provided, <gemeente> is assumed. If we cannot
	 * find it in the DB, convert to deelgemeente.
	 * @param string $sentence
	 * @return link
	 */
	public function search_vioe ($sentence) {
		/*https://inventaris.onroerenderfgoed.be/dibe/relicten?naam=&provincie=30000&gemeente=33011&deelgemeente_naam=Ieper&straat_naam=&typologie=kerken&datering=&stijl=&persoon_naam=&zoeken=zoeken*/
		$b_url = 'https://inventaris.onroerenderfgoed.be/dibe/relicten?naam=&provincie=%s&gemeente=%s&deelgemeente_naam=%s&straat_naam=&typologie=%s&datering=&stijl=&persoon_naam=&zoeken=zoeken';
		$url_values = array ();
		$urls = array ();
		/* Split string in 3 parts */
		$dc = $this->split_sentence ($sentence);
		/* First match, check whether it can be a name of something :TODO: */
		$what = $dc[1];
		/* Second match, check whether it is a gemeente or deelgemeente */
		/* Deelgemeente is not converted to a NIS-code */
		$url_values = $this->parse_loc ($dc);
		foreach ($url_values as $url_value) {
			array_push ($urls, array (sprintf ($b_url, $url_value['prov_n'], $url_value['gem_n'], ucfirst ($url_value['dg']), $url_value['q']), $dc));
		}
		return $urls;
	}

	/*
	 * Function to search for a $monument
	 * @param string $monument
	 * @return array $items
	 */
	public function monument_vioe ($sentence) {
		/* Split in 1+ parts */
		$parts = $this->split_sentence ($sentence);
		$qs = $this->parse_loc ($parts);
		/*match_monument ($monument, $use_like = false, $pid = null, $gid = null, $did = null)*/
		/*[1] => Array
        (
            [q] => kerk
            [prov_n] => 30000
            [gem_n] => 38016
            [dg] => Ramskapelle
            [pid] => 5
            [gid] => 161
            [did] => 554
        )*/
		$monuments = array ();
		foreach ($qs as $q) {
			array_push ($monuments, $this->m->match_monument ($q['q'], true, $q['pid'], $q['gid'], $q['did']));
		}
		return $monuments;
	}
}

?>