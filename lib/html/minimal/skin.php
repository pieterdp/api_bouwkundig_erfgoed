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
	 * Function to create the result page of a query for a monument
	 * @param string $title
	 * @param array $results
	 * @return $page
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
		foreach ($results as $result) {
			$i++;
			$r = $r.'<div class="result" id="result_'.$i.'">
'.$result.'
</div>';
		}
		$content = sprintf ($content, $r);
		return $content;
	}
}

?>