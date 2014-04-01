<?php
include_once ('lib/html_generator.php');
include_once ('lib/gemeentes.php');

$html = include_skin ('minimal');
$rp = new result_page ();
$glp = new glp ();

if (isset ($_GET['type']) && strtolower ($_GET['type']) == 'api') {
	/* API - get the query from the URL & return JSON-encoded string */
	$query = urldecode ($_GET['query']);
	$results = $glp->gemeente ($query);
	$json = array ('amount' => count ($results), 'results' => array ());
	foreach ($results as $result) {
		array_push ($json['results'], array ('query' => $query, 'result' => $result));
	}
	echo json_encode ($json);
	exit (0);
}

if (isset ($_POST['submit']) && $_POST['query'] != '') {
	/* Show results */
	$query = $_POST['query'];
	$results = $glp->gemeente ($query);
	$content = $rp->create_base_result ($rp->create_gemeente_result ($results, $query));
	$toc = '<div class="toc">
<ol>
<li><a href="#straat">Straten</a></li>
<li><a href="#deelgemeente">Deelgemeentes</a></li>
<li><a href="#gemeente">Gemeentes</a></li>
</ol>
</div>
';
	$content = $toc.$content;
	echo $html->create_base_page ('Zoeken naar gemeentes', $content);
} else {
	/* Show form */
	$form = '<form method="post" action="" id="nlp_form">
	<input type="text" size="64" name="query" id="query" />&nbsp;&nbsp;<input type="submit" value="Zoeken" id="smb" />
	<input type="hidden" name="submit" id="submit" value="1" />
</form>';
	$expl = '<h1>Zoeken naar gemeentes en straten</h1>
<p>Met deze \'API\' kan je zoeken naar de namen van straten of van gemeentes en deelgemeentes binnen de provincie West-Vlaanderen. De resultaten bestaan uit een trio van <em>gemeente</em> &mdash; <em>deelgemeente</em> &mdash; <em>straat</em></p>
<p>Zoeken gebeurt in een zin:</p>
<ul>
<li><strong>straat in gemeente/deelgemeente/deelgemeente, gemeente</strong></li>
<li><strong>deelgemeente, gemeente</strong></li>
<li><strong>straat/gemeente/deelgemeente</strong></li>
</ul>
<ul>
<li><strong>straat</strong> &mdash; naam of deel van de naam van een straat</li>
<li><strong>gemeente OF deelgemeente OF deelgemeente, gemeente</strong> &mdash; je kan zoeken naar een bepaalde straat in een deelgemeente (zonder een gemeente te specifiëren), een gemeente (zonder deelgemeente) of een deelgemeente en een gemeente (gescheiden door een komma).</li>
<li>Je kan ook gewoon zoeken op straatnaam of gemeentenaam of deelgemeentenaam of een combinatie ervan (bv. enkel deelgemeente, gemeente). Het is wel belangrijk dat <em>straat</em> en <em>gemeente/deelgemeente/deelgemeente, gemeente</em> gescheiden zijn door <strong>in</strong> (e.g. <em>vermandereplein in avelgem</em> is goed, maar <em>vermandereplein avelgem</em> geeft geen resultaten). Deelgemeente en gemeente moeten altijd gescheiden zijn door een komma.</li>
</ul>
<h2>Resultaten</h2>
<p>
De resultaten zijn in de vorm van een tabel, waarbij de resultaten zijn onderverdeeld naar welk trefwoord (gemeente, deelgemeente of straat) overeenkwam met (delen van) de query. Ieder resultaat bestaat uit:
</p>
<ul>
<li>Naam van de straat</li>
<li>Naam van de deelgemeente</li>
<li>Naam van de gemeente</li>
</ul>
<h2>Echte API</h2>
<p>De echte API is te benaderen via <a href="gemeenteswvl.php?type=API&amp;query=QUERY&amp;qt=TYPE" id="api">index.php?type=API&amp;query=QUERY</a>, waarbij QUERY de zin is die hier in het formulier kan worden ingevuld (zie voorbeelden). De string moet gecodeerd zijn met <a href="http://be2.php.net/manual/en/function.urlencode.php">urlencode</a>. Output is in JSON-formaat.</p>
';
	echo $html->create_base_page ('Zoeken naar monumenten', $form.$expl);
}

exit (0);
?>