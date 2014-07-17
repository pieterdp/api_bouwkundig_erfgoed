<?php

class xml_generator {

	public $e;
	protected $output_file;
	protected $xml;

	/*
	 * $a = as_string or as_file
	 * if $a = as_file, $filename is the name of the output file
	 */
	function __construct ($a, $filename = null) {
		if ($a == 'as_file' && $filename == null) {
			$this->e = "Error: no output filename set.";
			return false;
		}
		$this->xml = new XMLWriter ();
		if ($a == 'as_file') {
			$this->output_file = $filename;
			$this->xml->openURI ($filename);
		} elseif ($a == 'as_string') {
			$this->xml->openMemory ();
		} else {
			$this->e = "Error: illegal output option set (as_string or as_file).";
			return false;
		}
	}

	/*
	 * Function to write an XML file/string with all gemeentes
	 * @param array $gemeentes(i) = array (id, gemeente, deelgemeentes(i) = array (id, name))
	 * @return string/true
	 */
	public function export_gemeentes ($gemeentes) {
		$this->xml->startDocument ('1.0', 'UTF-8');
		$this->xml->setIndent (4);
		$this->xml->startElement ('gemeentes');
		foreach ($gemeentes as $g) {
			$this->xml->startElement ('gemeente');
			$this->xml->writeAttribute ('id', $g['id']);
			$this->xml->writeAttribute ('name', $g['gemeente']);
			$this->xml->writeElement ('id', $g['id']);
			$this->xml->writeElement ('name', $g['gemeente']);
			/* Deelgemeentes */
			$this->xml->startElement ('deelgemeentes');
			foreach ($g['deelgemeentes'] as $d) {
				$this->xml->startElement ('deelgemeente');
				$this->xml->writeAttribute ('id', $d['id']);
				$this->xml->writeAttribute ('name', $d['deelgemeente']);
				$this->xml->writeElement ('id', $d['id']);
				$this->xml->writeElement ('name', $d['deelgemeente']);
				$this->xml->endElement (); /* Deelgemeente */
			}
			$this->xml->endElement (); /* Deelgemeentes */
			$this->xml->endElement (); /* Gemeente */
		}
		$this->xml->endElement (); /* Gemeentes */
		$this->xml->endDocument ();
		return $this->xml->flush ();
	}

	/*
	 * Function to generate a xml-conversion file
	 * Structure:
	 *		<conversion>
				<job id="" name="">
					<fields>
						<field name="">
							<source></source>
							<destination></destination>
							<merge>
								<prefix></prefix>
								<suffix></suffix>
							</merge>
							<split>
								<spliton></spliton>
								<splitdest></splitdest>
							</split>
							<delete>
							</delete>
						</field>
						<field>...</field>
					</fields>
				</job>
				<job>...</job>
			</conversion>
	 * @param array $jobs[i] = array (id, name, fields[j] = array (name, source, destination, actions[type] = array (options => , prefix etc. =>))
	 * @return xml->flush ()
	 */
	public function generate_conversion_xml ($jobs) {
		$this->xml->startDocument ('1.0', 'UTF-8');
		$this->xml->setIndent (4);
		$this->xml->startElement ('conversion');
		foreach ($jobs as $job) {
			$this->xml->startElement ('job');
			$this->xml->writeAttribute ('id', $job['id']);
			$this->xml->writeAttribute ('name', $job['name']);
			$this->xml->startElement ('fields');
			/* Write the fields */
			foreach ($job['fields'] as $field) {
				$this->xml->startElement ('field');
				$this->xml->writeAttribute ('name', $field['name']);
				$this->xml->writeElement ('source', $field['source']);
				$this->xml->writeElement ('destination', $field['destination']);
				$actions = array ('split', 'merge', 'delete');
				foreach ($actions as $action) {
					if (isset ($field[$action]) && $field[$action] != '') {
						$this->xml->startElement ($action);
						switch ($action) {
							case 'split':
								$this->xml->writeAttribute ('options', $value);
								$this->xml->writeElement ('spliton', $field[$action]['spliton']);
								foreach ($field[$action]['splitdest'] as $splitdest) {
									$this->xml->writeElement ('splitdest', $splitdest);
								}
							break;
							case 'merge':
								if (isset ($field[$action]['prefix']) && $field[$action]['prefix'] != '') {
									$this->xml->writeElement ('prefix', $field[$action]['prefix']);
								}
								if (isset ($field[$action]['suffix']) && $field[$action]['suffix'] != '') {
									$this->xml->writeElement ('suffix', $field[$action]['suffix']);
								}
							break;
							case 'delete':
							break;
						}
						$this->xml->fullEndElement (); /* Action */
					}
				}
				/* Write the actions */
#				foreach ($field['actions'] as $action) { /* $field['actions'] = array () */
#					foreach ($action as $type => $params) { /* $action = array (type => params */
#						if (is_array ($params)) {
#							$this->xml->startElement ($type);
#							foreach ($params as $key => $value) { /* $params = array (key => value) */
#								/* Way too much nesting */
#								if ($key == 'options') {
#									$this->xml->writeAttribute ('options', $value);
#								} elseif ($key == 'splitdest') {
#									if (is_array ($value)) {
#										foreach ($value as $splitdest) {
#											$this->xml->writeElement ('splitdest', $splitdest);
#											}
#									}
#								} elseif ($value != '') {
#									$this->xml->writeElement ($key, $value);
#								}
#							}
#							$this->xml->fullEndElement; /* Type */
#						} else {
#							$this->xml->writeElement ($type, '');
#						}
#					}
#				}
				$this->xml->fullEndElement (); /* Field */
			}
			$this->xml->endElement (); /* Fields */
			$this->xml->endElement (); /* Job */
		}
		$this->xml->endElement (); /* Conversion */
		$this->xml->endDocument ();
		return $this->xml->flush ();
	}
}

?>