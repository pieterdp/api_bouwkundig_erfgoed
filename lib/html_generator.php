<?php

/*
 * Function to include a skin. This function
 * is called in the application and will
 * use include () to include the appriopriate
 * php-files. It returns the class reference
 * to $skin
 */

function include_skin ($skin = null) {
	if ($skin == null || isset ($skin) == false) {
		$skin = 'minimal'; // Default
	}
	$skin_path = 'lib/html/'.$skin.'/';
	if (!file_exists ($skin_path.'skin.php')) {
		die ("Error: skin file does not exist in skin directory $skin_path. Exiting.");
		exit (999);
	}
	include ($skin_path.'skin.php');
	if (file_exists ($skin_path.'results.php')) {
		include ($skin_path.'results.php');
	}
	$soop = new skin ();
	return $soop;
}

?>