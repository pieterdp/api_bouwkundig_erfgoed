<?php
require_once ('lib/class_uri_place.php');
require_once ('lib/class_xml_place.php');

/*
 * Script to translate between an URI-ID (?id=foo)
 * and a XML (EDM) place response for that ID
 */

$xml = new xml_edm ('nl_BE');
$u = new uri_place ();

if (!isset ($_GET['id'])) {
	header ('location: http://erfgoeddb.helptux.be/vioe.php');
	exit (0);
}

/* It's XML */
header ('Content-type: text/xml');

$uri_id = $_GET['id'];
$base_uri = 'http://erfgoeddb.helptux.be/uri/place/'.$uri_id;
$real_item = $u->get_id_by_uri_id ($uri_id);
if (!is_callable (array ($u, 'fetch_'.$real_item['entity_type']))) {
	echo "Error: function fetch_".$real_item[0]." does not exist!";
	exit (2);
}
$uri_response = call_user_func (array ($u, 'fetch_'.$real_item['entity_type']), $real_item['entity_id']);
/* URL = http://erfgoeddb.helptux.be/uri/place/uri_id */
/* Create uri's for every child */

/* Create XML node */
$xml_node = $xml->create_place_node ($uri_response['naam'], $real_item['entity_type'], $base_uri);
$xml_node = $xml->add_geo_to_node ($xml_node, $uri_response['wgs84_lat'], $uri_response ['wgs84_long']);
if (isset ($uri_response['children'])) {
	$child_uris = array ();
	foreach ($uri_response['children'] as $child) {
		array_push ($child_uris, 'http://erfgoeddb.helptux.be/uri/place/'.$child);
	}
	$xml_node = $xml->form_hierarchy ($xml_node, $child_uris, 'has_part');
}
if (isset ($uri_response['parents'])) {
	$parent_uris = array ();
	foreach ($uri_response['parents'] as $parent) {
		array_push ($parent_uris, 'http://erfgoeddb.helptux.be/uri/place/'.$parent);
	}
	$xml_node = $xml->form_hierarchy ($xml_node, $parent_uris, 'is_part_of');
}

$hierarchy = array (
	'provincies' => 300000774,
	'gemeentes' => 300265612,
	'deelgemeentes' => 300000778,
	'straten' => 300008247,
	'relicten' => 300006958,
	'adres' => 300248479
);
/* Add hierarchy */
$xml_node = $xml->dc_type ($xml_node, $hierarchy[$real_item['entity_type']]);

/* Print XML node */

$xml->add_node_to_wrapper ($xml_node);

echo $xml->create_xml_response ();
?>
