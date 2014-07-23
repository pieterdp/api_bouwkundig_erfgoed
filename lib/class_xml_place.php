<?php
include_once ('xml_generator.php');

class xml_place extends xml_generator {

	protected $dom; /* Reference to DOMDocument */

	function __construct () {
		$this->dom = new DOMDocument ('1.0', 'UTF-8');
	}

	/*
	 * Uses SKOS to express placenames in XML
	 */
	public function parse_place_as_xml () {
	}

	/*
	 * Function to convert one place item (e.g. gemeente, deelgemeente, straat) to SKOS
	 * @param string $name
	 * @param string $type
	 * @param $parent (DOMDocument::DOMElement)
	 * @return $node (DOMDOcument::DOMElement)
	 */
	//http://www.unc.edu/~prjsmith/skos_guide.html
	protected function create_place_node ($name, $type, $parent = null) {	
	}

	/*
	 * Function to create a SKOS concept
	 * @param string $name
	 * @param optional string $îd
}

?>