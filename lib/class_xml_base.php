<?php
/*
 * Class serving as the base for all xml-related subclasses
 */

class xml_base {

	protected $dom; /* Reference to DOMDocument */
	protected $lang; /* Default fall-back language when $lang is not defined in a function */
	protected $xml_lang; /* Language for all elements in the DOM DOcument (fall-back - may be overridden */

	function __construct ($lang = null) {
		$this->dom = new DOMDocument ('1.0', 'UTF-8');
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput = true;
		$this->lang = ($lang != null) ? $lang : 'nl-BE';
		$this->xml_lang = $this->create_xml_lang ($this->lang);
	}

	/*
	 * Function to create the xml:lang attribute
	 * @param string $lang
	 * @return DOMAttr $xml_lang
	 */
	protected function create_xml_lang ($lang) {
		$xml_lang = $this->dom->createAttribute ('xml:lang');
		$xml_lang->value = $lang;
		return $xml_lang;
	}

	/*
	 * Function to import a file
	 * @param string $file_location
	 * @return domDocument $file
	 */
	protected function import_xml_file ($location) {
		$file = new DOMDocument ();
		if ($file->load ($location) != true) {
			echo "Error: file at $location could not be loaded!";
			return false;
		}
		return $file;
	}
	/*
	 * Function to get a skos-element from the AAT webservice (SKOS)
	 * @param string $aat_id
	 * @param string $element
	 * @param string $lang
	 * @return DOMNode $node
	 */
	protected function get_skos_aat ($aat_id, $element, $lang) {
		$aat_file = $this->import_xml_file ('http://service.aat-ned.nl/skos/'.$aat_id);
		$nodes = $aat_file->getElementsByTagNameNS ('http://www.w3.org/2004/02/skos/core#', $element);
		foreach ($nodes as $node) {
			$attributes = $node->attributes;
			$n_lang = $attributes->getNamedItem ('xml:lang');
			if ($n_lang->value == $lang) {
				return $node;
			}
		}
		return false;
	}
}
?>
