<?php
include_once ('lib/nlp.php');
include_once ('lib/html_generator.php');

$html = include_skin ('minimal');
$rp = new result_page ();
$nlp = new nlp ();
$ids_per_page = 10;


if (isset ($_GET['type']) && strtolower ($_GET['type']) == 'api') {
	/* API - get the query from the URL & return JSON-encoded string */
	$query = $_GET;
	$query['db'] = 'vioe';
	$newurl = 'http://erfgoeddb.helptux.be/api.php?'.http_build_query ($query);
	header ('Location: '.$newurl);
	header ('HTTP/1.0 301 Moved Permanently');
	exit (0);
}


/* Form */
if (isset ($_POST['submit'])) {
	/* Show results */
	$query = $_POST['query'];
	if ($_POST['type'] == 'typo') {
		$results = $nlp->search_vioe ($query);
		$expl = '<h1>Resultaten</h1>
	<p>Jouw zoektocht naar "<span class="code">'.$query.'</span>" leverde '.count ($results).' ';
		if (count ($results) != 1) {
			$expl = $expl.'resultaten';
		} else {
			$expl = $expl.'resultaat';
		}
		$expl = $expl.' op. Klik op de links om naar de resultaatpagina van de inventaris te gaan.</p>';
		$r_out = '<ul>
	%s
	</ul>';
		foreach ($results as $result) {
			$rs = $rs.'
	<li><a href="'.htmlspecialchars (stripslashes ($result[0])).'">'.htmlspecialchars ($result[0]).'</a></li>';
		}
		if (count ($results) == 1) {
			header ("location: ".stripslashes ($result[0]), true);
			exit (0);
		}
		echo $html->create_base_page ('Zoeken naar monumenten', $expl.sprintf ($r_out, $rs));
	} elseif ($_POST['type'] == 'mon') {
		$list_of_ids = $nlp->monument_vioe ($query);
		/* Flatten */
		/*$f_r = array ();
		foreach ($list_of_ids as $key => $r) {
			if ($key != 'total') {
				$f_r = array_merge ($f_r, $r);
			}
		}*/
		$f_r = $list_of_ids;
		/* Get total amount */
		$total = count ($list_of_ids);
		$expl = '<h1>Resultaten</h1>
	<p>Jouw zoektocht naar "<emp>'.$query.'</emp>" leverde '.$total.' ';
		if (count ($f_r) != 1) {
			$expl = $expl.'resultaten';
		} else {
			$expl = $expl.'resultaat';
		}
		$expl = $expl.' op.<br/><span style="copy-notice">(gebaseerd op data uit de Inventaris Onroerend Erfgoed)</span></p>';
		$rx = $rp->create_monument_result ($f_r);
		$content = $expl.$rp->create_base_result ($rx);
		echo $html->create_base_page ('Zoeken naar monumenten', $content, '<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />', '<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
<script type="text/javascript" src="lib/html/minimal/map.js"></script>');
	}
} else {
	/* Show form */
	$form = '<form method="post" action="" id="nlp_form">
	<input type="text" size="64" name="query" id="query" />&nbsp;&nbsp;<input type="submit" value="Zoeken" id="smb" />
	<input type="hidden" name="submit" id="submit" value="1" />
	<br/> <input type="radio" name="type" id="type" value="typo" />&nbsp;Typologie&nbsp;&nbsp;<input type="radio" name="type" id="type" value="mon" />&nbsp;Monument
</form>';
	$expl = '<h1>Zoeken naar monumenten</h1>
<p>Met deze \'API\' kan je zoeken naar bepaalde soorten (=typologie) monumenten of naar de monumenten zelf in gemeentes (in Vlaanderen). Dit is een soort \'wrapper\' rond het zoekformulier van de Inventaris Onroerend Erfgoed, omdat die niet zo gebruiksvriendelijk is.</p>
<p>Als je zoekt op soorten monumenten krijg je de URL van de zoekpagina van de Inventaris terug (typologieën worden niet geëxporteerd); als je zoekt op monumenten krijg je resultaten uit de eigen database (gebaseerd op een export van de Inventaris).</p>
<p>Zoeken gebeurt in een zin:
<br/><strong>typologie/monument in gemeente/deelgemeente/deelgemeente, gemeente</strong></p>
<ul>
<li><strong>typologie OF monument</strong> &mdash; typologie zoals in de Inventaris (bv. kerken) of (een deel van) de naam van een monument</li>
<li><strong>gemeente OF deelgemeente OF deelgemeente, gemeente</strong> &mdash; je kan zoeken naar een bepaalde typologie in een deelgemeente (zonder een gemeente te specifiëren), een gemeente (zonder deelgemeente) of een deelgemeente en een gemeente (gescheiden door een komma).</li>
</ul>
<h2>Resultaten</h2>
<p>
Bij het zoeken naar een typologie krijg je een lijst URL\'s die verwijzen naar de resultaatpagina\'s van de Inventaris Onroerend Erfgoed.
</p>
<p>
Bij het zoeken naar monumenten krijg je een lijst resultaten uit de eigen database (gebaseerd op een export van de Inventaris) met voor ieder resultaat:
<ul>
<li>Naam van het monument</li>
<li>Adres van het monument (straat, nummer, deelgemeente, hoofdgemeente en provincie)</li>
<li>Geolocatie van het monument (in WGS84-formaat)</li>
<li>URL die verwijst naar de Inventaris</li>
</ul>
</p>
<h2>Echte API</h2>
<p>De echte API is te benaderen via <a href="vioe.php?type=API&amp;query=QUERY&amp;qt=TYPE" id="api">vioe.php?type=API&amp;query=QUERY&amp;qt=TYPE</a>, waarbij QUERY de zin is die hier in het formulier kan worden ingevuld (zie voorbeelden) en TYPE of er gezocht wordt op typologieën (<em>&amp;qt=typo</em>) of monumenten (<em>&amp;qt=mon</em>). De string moet gecodeerd zijn met <a href="http://be2.php.net/manual/en/function.urlencode.php">urlencode</a>. Output is in JSON-formaat.</p>
<h2>Voorbeelden</h2>
<ul>
<li><strong>kerken in Ieper, Ieper</strong> &mdash; zoekt alle kerken in de deelgemeente Ieper van de hoofdgemeente Ieper</li>
<li><strong>gevangenissen in Brugge</strong> &mdash; zoekt alle gevangenissen in de hoofdgemeente Brugge</li>
<li><strong>herenhuizen in Ramskapelle</strong> &mdash; zoekt alle herenhuizen in de deelgemeente Ramskapelle (dit zijn alle deelgemeentes die Ramskapelle heten!)</li>
<li><strong><a href="api.php?db=vioe&amp;output=json&amp;query='.urlencode ('herenhuizen in Ramskapelle').'&amp;qt=typo">herenhuizen in Ramskapelle</a></strong> &mdash; API-query voor <emp>herenhuizen in Ramskapelle</emp>.</li>
<li><strong>Parochiekerk Sint-Maarten in Sint-Truiden</strong> &mdash; zoekt naar de Parochiekerk Sint-Maarten in de deelgemeente Sint-Truiden (dit zijn alle deelgemeentes die Sint-Truiden heten!)</li>
<li><strong><a href="api.php?db=vioe&amp;output=json&amp;query='.urlencode ('Parochiekerk Sint-Maarten in Sint-Truiden').'&amp;qt=mon">Parochiekerk Sint-Maarten in Sint-Truiden</a></strong> &mdash; API-query voor <emp>Parochiekerk Sint-Maarten in Sint-Truiden</emp>.</li>

</ul>';
	echo $html->create_base_page ('Zoeken naar monumenten', $form.$expl);
}

exit (0);

$sentence = 'kerken in Ieper, Ieper';

print_r ($nlp->search_vioe ($sentence));


$sentence = 'gevangenissen in Mariakerke';
print_r ($nlp->search_vioe ($sentence));
?>
