<?php
include_once ('skin.php');
/* Results of search queries */
class result_page extends skin {

	/*
	 * Function to create the result page of a query for a gemeente - straat combo
	 * @param string $title
	 * @param array $results (output from $glp->gemeente ()
	 * @return $content
	 */
	public function create_gemeente_result ($results, $query) {
		/*[2] => Array
        (
            [q] => Bossuit
            [g] => Spiere-Helkijn
            [dg] => Helkijn
            [s] => Weg naar Bossuit
        )
		  [6] => Array
        (
            [q] => Bossuit
            [g] => Avelgem
            [dg] => Bossuit
            [s] => Array
                (
                    [0] => Array
                        (
                            [id] => 7213
                            [name] => Kanaalweg
                            [wgs84_lat] => 50,7546942926
                            [wgs84_long] => 3,4082455291
                            [dg_id] => 107
                        )
			*/
		$wrapper = '<h1 class="result">Resultaten</h1>
<span style="back-button"><a href="gemeenteswvl.php">&lt;&lt;&nbsp;terug</a></span>
<p>De zoekopdracht <span class="code">%s</span> leverde %d %s op.</p>
%s
';
		$table = '<table class="result gem">
	<tr class="result gem">
		<th class="result">Gemeente</th>
		<th class="result">Deelgemeente</th>
		<th class="result">Straat</th>
	</tr>
		%s
</table>';
		$row = '<tr class="result gem">
			<td class="result gem">%s</td>
			<td class="result gem">%s</td>
			<td class="result gem">%s</td>
</tr>';
		$ordered_results = array ( /* Order results: keys in this array are on which element the query matched, e.g. query matched a gemeente, here are the results from that gemeente etc. */
			'straat' => array (),
			'deelgemeente' => array (),
			'gemeente' => array ()
		);
		/* Order results */
		foreach ($results as $r) {
			switch ($r['t']) {
				case 'straat':
					array_push ($ordered_results['straat'], $r);
				break;
				case 'deelgemeente':
					array_push ($ordered_results['deelgemeente'], $r);
				break;
				case 'gemeente':
					array_push ($ordered_results['gemeente'], $r);
				break;
			}
		}
		$c = ''; /* Main content area */
		foreach ($ordered_results as $key => $r) {
			$tbody = '';
			$i = 0;
			if (count ($r) != 0) {
				foreach ($r as $e) {
					if (is_array ($e['s'])) {
					/* Straten is an array - just add them all */
					foreach ($e['s'] as $s) {
						$i++;
						$tbody = $tbody.sprintf ($row,
							htmlentities ($e['g']),
							htmlentities ($e['dg']),
							htmlentities ($s['name'])
						);
						}
					} else {
						$i++;
						$tbody = $tbody.sprintf ($row,
							htmlentities ($e['g']),
							htmlentities ($e['dg']),
							htmlentities ($e['s'])
						);
					}
				}
				$ar = $i;
				$a = $a + $ar;
				$c = $c.'<h2><a class="h2" id="'.$key.'">... als '.$key.' ('.$ar.')</a></h2>
	';
				$c = $c.sprintf ($table, $tbody);
			}
		}
		/*
		foreach ($results as $r) {
			$i++;
			if (is_array ($r['s'])) {
				/* Straten is an array - just add them all *//*
				foreach ($r['s'] as $s) {
					$tbody = $tbody.sprintf ($row,
						$i, htmlentities ($r['g']),
						$i, htmlentities ($r['dg']),
						$i, htmlentities ($s['name'])
					);
				}
			} else {
				$tbody = $tbody.sprintf ($row,
						$i, htmlentities ($r['g']),
						$i, htmlentities ($r['dg']),
						$i, htmlentities ($r['s'])
					);
			}
		}*/
		/* Amount of results */
		$rm = 'resultaten';
		if ($a == 1) {
			$rm = 'resultaat';
		}
		return sprintf ($wrapper, $query, $a, $rm, $c);
	}

	/*
	 * Function to create the result page of a query for a monument
	 * @param string $title
	 * @param array $results
	 * @return $content
	 */
	public function create_monument_result ($results) {
		/*[2] => Array
        (
            [naam] => Kerktoren parochiekerk Sint-Laurentius
            [url] => http://inventaris.vioe.be/dibe/relict/10761
            [adres] => Array
                (
                    [straat] => Te Couwelaarlei
                    [nummer] => zonder nummer
                    [deelgem] => Antwerpen
                    [gem] => Antwerpen
                    [prov] => Antwerpen
                    [wgs84_lat] => 0
                    [wgs84_long] => 0
                )

        )*/
		$rh_base = '<h3 class="result">%s</h3>
<table class="result" id="result_detail_%d">
%s
<tr class="result">
	<td class="result map" id="maptd_%d"><div id="map_%d" class="map"></div></td>
</tr>
</table>';
		$tr_base = '
<tr class="result hidden">
	<td class="result" id="monument_%d">%s</td>
</tr>
<tr class="result">
	<td class="result" id="adres_%d_a%d"><span class="straat" id="straat_%d_a%d">%s %s</span> in <span class="deelgemeente" id="deelgemeente_%d_a%d">%s</span>, <span class="gemeente" id="gemeente_%d_a%d">%s</span> (provincie <span class="provincie" id="provincie_%d_a%d">%s</span>)</td>
</tr>
<tr class="result">
	<td class="result coord" id="coord_%d_a%d">LAT: <span class="coord" id="wgs84_lat_%d_a%d">%s</span>, LONG: <span class="coord" id="wgs84_long_%d_a%d">%s</span></td>
</tr>
';
		$i = 0;
		$rh = array ();
		foreach ($results as $result) {
			$i++;
			$rh_addr = '';
			$j = 0;
			foreach ($result['adres'] as $addres) {
				$j++;
				$rh_addr = $rh_addr.sprintf ($tr_base,
					$i, $result['naam'],
					$i, $j, $i, $j, $addres['straat'], $addres['nummer'], $i, $j, $addres['deelgemeente'], $i, $j, $addres['gemeente'], $i, $j, $addres['provincie'],
					$i, $j, $i, $j, $addres['wgs84_lat'], $i, $j, $addres['wgs84_long']
				);
			}
			array_push ($rh, sprintf ($rh_base,
				$result['naam'],
				$i,
				'<tr class=" result hidden">
					<td class="result" id="'.$i.'_a">'.$j.'</td>
				</tr>
				'.$rh_addr,
				$i, $i
			));
			$rh['total'] = $i;
		}
		return $rh;
	}

	/*
	 * Create a base result page
	 * @param string $title
	 * @param array $results: array with the HTML for the results (those results are embedded in divs)
	 * @return string $content
	 */
	public function create_base_result ($results) {
		$content = '<div class="results" id="results">
%s
</div>';
		$r = '';
		$i = 0;
		if (is_array ($results)) {
			foreach ($results as $key => $result) {
				if ($key == 'total' && is_numeric ($result) === true) { /* Ugly Hack */
					/* Small hack to make maps work (javascript) */
					$r = $r.'<div class="hidden" id="totalitems">'.$result.'</div>'."\n";
					continue;
				}
				$i++;
				$r = $r.'<div class="result" id="result_'.$i.'">
	'.$result.'
	</div>';
			}
		} else {
			$r = $results;
		}
		$content = sprintf ($content, $r);
		return $content;
	}
}

?>
