<?php

class db_connect {
	
	protected $c;

	function __construct () {
		if (file_exists ('etc/config.php')) {
			include ('etc/config.php');
		} else {
			die ("Error: configuration file not found.");
		}
		$this->c = new mysqli ($db['host'], $db['username'], $db['password'], $db['database']);
	}
}

?>
