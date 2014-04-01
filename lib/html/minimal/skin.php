<?php
include_once ('lib/class_html.php');

class skin extends html_generator {

	public $tm; /* Topmenu */
	public $fm; /* Footer menu */
	public $c; /* Content */

	/*
	 * Create base page (supersedes the one in class_html
	 */
	public function create_base_page ($title, $content) {
		return str_replace (array ('[TITLE]', '[TOPMENU]', '[CONTENT]', '[FOOTMENU]'), array ($title, $this->topmenu (), $content, $this->footmenu ()), $this->tmpl);
	}
	
	/*
	 * Generate the menu
	 * @param array $menu_items = array ('item_name' => 'link')
	 * @param string $active_item name of the active item (optional) - gets class="active"
	 * @return $string topmenu
	 */
	public function topmenu ($menu_items = null, $active_item = null) {
		if ($menu_items == null) {
			$menu_items = array (
				'home' => '',
				'apps' => 'apps.php',
				'blog' => 'blog.php'
			);
		}
		$menu_items = array_reverse ($menu_items); /* Reverse the order, as these elements are floated and thus last one is shown first */
		$tmtmpl = $this->load_template ('default-menu');
		$this->tm = '';
		$i = 1;
		foreach ($menu_items as $name => $link) {
			if ($name != $active_item || $active_item == null) {
				$this->tm = $this->tm.str_replace (array ('[N]', '[ITEM]', '[LINK]', '[CLASS]'), array ($i, $name, $link, ''), $tmtmpl);
			} else {
				$this->tm = $this->tm.str_replace (array ('[N]', '[ITEM]', '[LINK]', '[CLASS]'), array ($i, $name, $link, ' active'), $tmtmpl);
			}
			$i++;
		}
		return $this->tm;
	}

	/*
	 * Generate the footmenu
	 * @param array $menu_items = array ('item_name' => 'link')
	 * @return $string footmenu
	 */
	public function footmenu ($menu_items = null) {
		if ($menu_items == null) {
			$menu_items = array (
				'about' => 'about.php',
				'contact' => 'contact.php',
				'sitemap' => 'sitemap.php'
			);
		}
		$menu_items = array_reverse ($menu_items); /* Reverse the order, as these elements are floated and thus last one is shown first */
		$tmtmpl = $this->load_template ('default-footer');
		$this->tm = '';
		$i = 1;
		foreach ($menu_items as $name => $link) {
			$this->tm = $this->tm.str_replace (array ('[N]', '[ITEM]', '[LINK]'), array ($i, $name, $link), $tmtmpl);
			$i++;
		}
		return $this->tm;
	}
}

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
<tr class="result hidden">
	<td class="result" id="monument_%d">%s</td>
</tr>
<tr class="result">
	<td class="result" id="adres_%d"><span class="straat" id="straat_%d">%s %s</span> in <span class="deelgemeente" id="deelgemeente_%d">%s</span>, <span class="gemeente" id="gemeente_%d">%s</span> (provincie <span class="provincie" id="provincie_%d">%s</span>)</td>
</tr>
<tr class="result">
	<td class="result url" id="url_%d"><a href="%s">[VIOE]</a></td>
</tr>
<tr class="result">
	<td class="result coord" id="coord_%d">LAT: <span class="coord" id="wgs84_lat_%d">%s</span>, LONG: <span class="coord" id="wgs84_long_%d">%s</span></td>
</tr>
</table>';
		$i = 0;
		$rh = array ();
		foreach ($results as $result) {
			$i++;
			array_push ($rh, sprintf ($rh_base,
				$result['naam'],
				$i,
				$i, $result['naam'],
				$i, $i, $result['adres']['straat'], $result['adres']['nummer'], $i, $result['adres']['deelgem'], $i, $result['adres']['gem'], $i, $result['adres']['prov'],
				$i, $result['url'],
				$i, $i, $result['adres']['wgs84_lat'], $i, $result['adres']['wgs84_long']
			));
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
			foreach ($results as $result) {
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