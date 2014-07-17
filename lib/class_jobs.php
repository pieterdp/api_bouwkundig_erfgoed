<?php

include_once ('lib/xml_generator.php');

class job_creator {

	protected $job_id;
	protected $xml;
	public $jparameters = array ();
	public $e;

	/*
	 * joptions = array (i => fields, jobname, fields => array (fieldname => value (can be array)))
	*/
	function __construct ($joptions) {
		$this->xml = new xml_generator ('as_string');
		
		if ($this->parse_job ($joptions) != true) {
			$this->e = "Error: fout tijdens het parsen van joptions.";
			exit (9);
		}
	}

	/*
	 * Function to parse a new job
	 * @param array $joptions
	 * @return true/false
	 */
	protected function parse_job ($joptions) {
		/* @param array $jobs[i] = array (id, name, fields[j] = array (name, source, destination, actions[type] = array (options => , prefix etc. =>))
	 * */
		$this->jparameters['id'] = $this->job_id ($joptions['jobname'], $joptions['i']);
		$this->jparameters['name'] = $joptions['jobname'];
		$jfields = array (); /* For use in jparameters */
		$actions = array ('merge', 'split', 'delete'); /* Possible actions */
		foreach ($joptions['fields'] as $field) {
			$jfield = array (); /* Single field in jfields */
			$jfield['name'] = $field['name'];
			$jfield['source'] = $field['source'];
			$jfield['destination'] = $field['dest'];
			/* Actions */
			/* $jfield[name] = options
			) */
			foreach ($actions as $action) {
				if (isset ($field['action-'.$action]) && $field['action-'.$action] == '1') {
					$jfield[$action] = array ();
					switch ($action) {
						case 'split':
							$jfield[$action] = array (
								'options' => $field['splitoptions'],
								'spliton' => $field['spliton'],
								'splitdest' => $field['splitdest']
							);
						break;
						case 'merge':
							$jfield[$action] = array (
								'prefix' => $field['prefix'],
								'suffix' => $field['suffix']
							);
						break;
						case 'delete':
							$jfield[$action] = true;
						break;
					}
				}
			}
#			$jfield['actions'] = array ();
#			$jaction = array (); /* type => options */
#			foreach ($actions as $action) {
#				if ($field['action-'.$action] == '1') {
#					$jaoptions = array (); /* key => value */
#					/* Action selected */
#					switch ($action) {
#						case 'split':
#							$jaoptions['options'] = $field['splitoptions'];
#							$jaoptions['splitdest'] = $field['splitdest'];
#							$jaoptions['spliton'] = $field['spliton'];
#							$jaction['split'] = $jaoptions;
#						break;
#						case 'merge':
#							$jaoptions['prefix'] = $field['prefix'];
#							$jaoptions['suffix'] = $field['suffix'];
#							$jaction['merge'] = $jaoptions;
#						break;
#						case 'delete':
#							$jaction['delete'] = true;
#						break;
#					}
#				}
#			}
#			array_push ($jfield['actions'], $jaction);
			array_push ($jfields, $jfield);
		}
		$this->jparameters['fields'] = $jfields;
		return true;
	}

	/*
	 * Function to generate the job_id
	 * @return $job_id
	 */
	protected function job_id ($job_name, $job_fields) {
		$this->job_id = md5 ($job_name.$job_fields);
		return $this->job_id;
	}

	/*
	 * Function to parse $jparameters as xml
	 * @return string $output
	 */
	public function parse_as_xml () {
		$jobs = array ();
		array_push ($jobs, $this->jparameters);
		$output = $this->xml->generate_conversion_xml ($jobs);
		return $output;
	}
}


?>