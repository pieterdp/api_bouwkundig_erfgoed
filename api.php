<?php
include_once ('lib/nlp.php');

/*
 * API Calls (required):
 * db= name of the app you want to use (e.g. vioe, gem etc.)
 * output= output type (json (default), xml)
 *	as well as db-specific parameters
 */

$nlp = new nlp ();

$output; /* API-wide container for output (arrray), is then converted into the correct output type */

switch ($_GET['db']) {
	case 'vioe':
		$query = urldecode ($_GET['query']);
		if (!isset ($_GET['qt']) || $_GET['qt'] == 'typo') {
			$results = $nlp->search_vioe ($query);
			$r = array ('amount' => count ($results), 'results' => array ());
			foreach ($results as $result) {
				array_push ($r['results'], array ('query' => $query, 'url' => stripslashes ($result[0])));
			}
		} elseif ($_GET['qt'] == 'mon') {
			/* API for monuments */
			$results = $nlp->monument_vioe ($query);
			/* Flatten */
			$f_r = array ();
			foreach ($results as $r) {
				$f_r = array_merge ($f_r, $r);
			}
			$r = array ('amount' => count ($f_r), 'results' => array ());
			foreach ($f_r as $result) {
				if (defined ($result[0])) {
					array_push ($r['results'], array ('query' => $query, 'monument' => $result[0]));
				} else {
					array_push ($r['results'], array ('query' => $query, 'monument' => $result));
				}
			}
		}
		$output = $r;
	break;
	case 'gem':
	break;
	default:
	break;
}

switch ($_GET['output']) {
	case 'json':
		echo json_encode ($output);
	break;
	case 'xml':
	break;
	default:
	break;
}

exit (0);
?>