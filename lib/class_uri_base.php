<?php

require_once ('mysql_connect.php');

class uri_base extends db_connect {

	function __construct () {
		parent::__construct ();
	}

	/* URI table */
	public function create_uri_table () {
		$q = "SELECT DISTINCT id FROM %s";
		
	}
	
}

?>