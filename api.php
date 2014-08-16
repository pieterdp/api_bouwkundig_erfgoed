<?php
include_once ('lib/nlp.php');
include_once ('lib/class_xml_place.php');

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
			/*$f_r = array ();
			foreach ($results as $r) {
				$f_r = array_merge ($f_r, $r);
			}*/
			$r = array ('amount' => count ($results), 'results' => array ());
			foreach ($results as $result) {
				if (!is_array ($result[0]) && defined ($result[0])) {
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
		header ('Content-type: application/json');
		echo json_encode ($output);
		exit (0);
	break;
	case 'xml':
		header ('Content-type: application/xml');
		$xml = new xml_edm ();
		foreach ($output['results'] as $item) {
			if (is_array ($item)) {
				$el = $xml->parse_place_as_xml ($item['monument']);
				$xml->add_node_to_wrapper ($el);
			}
		}
		echo $xml->create_xml_response ();
		exit (0);
	break;
	default:
	break;
}

exit (0);
?>
