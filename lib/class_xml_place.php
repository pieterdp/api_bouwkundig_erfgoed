<?php
include_once ('class_xml_base.php');
//include_once ('class_uri_search.php');
/*
 * Creates a EDM:Place XML result from a DB place (consisting of any combination of provincie, gemeente, deelgemeente, straat, huisnummer, monument
 * We use EDM:Place 'cause it can be used within Europeana
 */

class xml_edm extends xml_base {

	protected $wrapper; /* rdf:RDF wrapper around all edm:Place entities */
	protected $u; /* class $uri search object */

	function __construct ($lang = null) {
		parent::__construct ($lang);
		$this->dom->appendChild ($this->dom->createProcessingInstruction ('xml-model', 'href="http://www.europeana.eu/schemas/edm/EDM.xsd" type="application/xml" schematypens="http://purl.oclc.org/dsdl/schematron"')); /* Add xml-model */
		$this->wrapper = $this->dom->createElementNS ('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF');
		$this->dom->appendChild ($this->wrapper);
		//$this->u = new uri_search ();
		/*<rdf:RDF xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.w3.org/1999/02/22-rdf-syntax-ns# EDM-INTERNAL.xsd"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:edm="http://www.europeana.eu/schemas/edm/"
 xmlns:wgs84_pos="http://www.w3.org/2003/01/geo/wgs84_pos#"
 xmlns:enrichment="http://www.europeana.eu/schemas/edm/enrichment/"
 xmlns:oai="http://www.openarchives.org/OAI/2.0/"
 xmlns:owl="http://www.w3.org/2002/07/owl#"
 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 xmlns:ore="http://www.openarchives.org/ore/terms/"
 xmlns:skos="http://www.w3.org/2004/02/skos/core#"
 xmlns:dcterms="http://purl.org/dc/terms/">*/
	}

	/*
	 * Function to create the xml response. Returns a string as DOM->saveXML
	 * @return string $xml_response
	 */
	public function create_xml_response () {
		return $this->dom->saveXML ();
	}

	/*
	 * Function to add a @param node to the wrapper with appendChild
	 * @param DOMElement $node
	 * @return true/false
	 */
	public function add_node_to_wrapper ($node) {
		$this->wrapper->appendChild ($node);
		return true;
	}

	/*
	 * Convert a standard query response of the type "monument" (warning! not the entire query - see class xml_api.php for that implementation) to edm:Place
	 * @param array $monument
	 * @param optional string $lang
	 * @return DOMEl $monument_node
	 */
	public function parse_place_as_xml ($monument, $lang = null) {
		/* Structure of $monument 
		 * monument = array (
		 		id => array ()
				naam => string
				url => string
				relict_id => string
				adres => array (
					array (
						straat => string
						deelgemeente => string
						gemeente => string
						provincie => string
						huisnummer => string
						wgs84_lat => string
						wgs84_long => string
						id => string
					) ...
				)
		 * )
		 */
		 $m_node;
		 $p_node;
		 $g_node;
		 $dg_node;
		 $s_node;
		 $h_node;
	}

	/*
	 * Function to convert one (minimal) place item (e.g. gemeente, deelgemeente, straat) to EDM:Place
	 * @param string $name
	 * @param string $type
	 * @param string $uri
	 * @param optional $lang (fall-back to $this->lang)
	 * @return $node (DOMDOcument::DOMElement)
	 */
	public function create_place_node ($name, $type, $uri, $lang = null) {
		/* Language */
		$xml_lang = ($lang != null) ? $this->create_xml_lang ($lang) : $this->xml_lang;
		/* Main node information */
		$placenode = $this->dom->createElementNS ('http://www.europeana.eu/schemas/edm/', 'edm:Place');
		$rdf_about = $this->dom->createAttributeNS ('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:about');
		$rdf_about->value = $uri;
		$placenode->appendChild ($rdf_about);
		/* Labels & note (SKOS) */
		$preflabel = $this->dom->createElementNS ('http://www.w3.org/2004/02/skos/core#', 'skos:prefLabel', $name);
		$preflabel->appendChild ($xml_lang);
		$note = $this->dom->createElementNS ('http://www.w3.org/2004/02/skos/core#', 'skos:note', $type);
		$note->appendChild ($xml_lang);
		/* Tie it together */
		$placenode->appendChild ($preflabel);
		$placenode->appendChild ($note);
		return $placenode;
	}

	/*
	 * Function to add geographical information to a place $node
	 * @param DOMEl $node
	 * @param string $wgs84_lat
	 * @param string $wgs84_long
	 * @param optional string $wgs84_alt
	 * @return DOMEl $node
	 */
	public function add_geo_to_node ($node, $wgs84_lat, $wgs84_long, $wgs84_alt = null) {
		$lat = $this->dom->createElementNS ('http://www.w3.org/2003/01/geo/wgs84_pos#', 'wgs84_pos:lat', $wgs84_lat);
		$long = $this->dom->createElementNS ('http://www.w3.org/2003/01/geo/wgs84_pos#', 'wgs84_pos:long', $wgs84_long);
		$node->appendChild ($lat);
		$node->appendChild ($long);
		if ($wgs84_alt != null) {
			$alt = $this->dom->createElementNS ('http://www.w3.org/2003/01/geo/wgs84_pos#', 'wgs84_pos:alt', $wgs84_alt);
			$node->appendChild ($alt);
		}
		return $node;
	}

	/*
	 * Function to form the hierarchical relation between a node and its parents/children
	 * @param DOMEl $node
	 * @param array $is_part_of OR $has_part OR $next_in_sequence (array of URI's)
	 * @param string $type (either is_part, has_part or next_in_sequence
	 * @return DOMEl $node
	 */
	public function form_hierarchy ($node, $relations, $type) {
		foreach ($relations as $relation) {
			$rel;
			$rdf_resource = $this->dom->createAttributeNS ('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:resource');
			$rdf_resource->value = $relation;
			switch ($type) {
				case 'is_part_of':
					$rel = $this->dom->createElementNS ('http://purl.org/dc/terms/', 'dcterms:isPartOf');
				break;
				case 'has_part':
					$rel = $this->dom->createElementNS ('http://purl.org/dc/terms/', 'dcterms:hasPart');
				break;
				case 'next_in_sequence':
					$rel = $this->dom->createElementNS ('http://www.europeana.eu/schemas/edm/', 'edm:isNextInSequence');
				break;
				default:
					echo "Error: illegal hierarchy specified in form_hierarchy!";
					return false;
				break;
			}
			$rel->appendChild ($rdf_resource);
			$node->appendChild ($rel);
		}
		return $node;
	}

	/*
	 * Function implementing owl:sameAs
	 * @param DOMEl $node
	 * @param string $uri (uri of the sameAs)
	 * @return DOMEl $node
	 */
	public function owl_same_as ($node, $uri) {
		$owl_sameas = $this->dom->createElementNS ('http://www.w3.org/2002/07/owl#', 'owl:sameAs');
		$rdf_resource = $this->dom->createAttributeNS ('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:resource');
		$rdf_resource->value = $uri;
		$owl_sameas->appendChild ($rdf_resource);
		$node->appendChild ($owl_sameas);
		return $node;
	}

	/*
	 * Function to implement skos:altLabel
	 * @param DOMEl $node
	 * @param string $alt_label
	 * @param optional string $lang
	 * @return DOMEl $node
	 */
	public function skos_alt_label ($node, $alt_label, $lang = null) {
		/* Language */
		$xml_lang = ($lang != null) ? $this->create_xml_lang ($lang) : $this->xml_lang;
		/* altLabel */
		$skos_altlabel = $this->dom->createElementNS ('http://www.w3.org/2004/02/skos/core#', 'skos:altLabel', $alt_label);
		$skos_altlabel->appendChild ($xml_lang);
		$node->appendChild ($skos_altlabel);
		return $node;
	}
}

?>
