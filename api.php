<?php
include_once ('lib/nlp.php');

$nlp = new nlp ();

if (isset ($_GET['type']) && strtolower ($_GET['type']) == 'vioe') {
	/* API - get the query from the URL & return JSON-encoded string */
	$query = urldecode ($_GET['query']);
	if (!isset ($_GET['qt']) || $_GET['qt'] == 'typo') {
		$results = $nlp->search_vioe ($query);
		$json = array ('amount' => count ($results), 'results' => array ());
		foreach ($results as $result) {
			array_push ($json['results'], array ('query' => $query, 'url' => stripslashes ($result[0])));
		}
	} elseif ($_GET['qt'] == 'mon') {
		/* API for monuments */
		$results = $nlp->monument_vioe ($query);
		/* Flatten */
		$f_r = array ();
		foreach ($results as $r) {
			$f_r = array_merge ($f_r, $r);
		}
		$json = array ('amount' => count ($f_r), 'results' => array ());
		foreach ($f_r as $result) {
			if (defined ($result[0])) {
				array_push ($json['results'], array ('query' => $query, 'monument' => $result[0]));
			} else {
				array_push ($json['results'], array ('query' => $query, 'monument' => $result));
			}
		}
	}
	echo json_encode ($json);
	exit (0);
}

?>