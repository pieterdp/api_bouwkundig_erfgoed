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
		$this->lang = ($lang != null) ? $lang : 'nl_BE';
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
}
?>