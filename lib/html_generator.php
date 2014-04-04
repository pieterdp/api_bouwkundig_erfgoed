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
	if (file_exists ($skin_path.'results.php')) {
		include ($skin_path.'results.php');
	}
	include ($skin_path.'skin.php');
	$soop = new skin ();
	return $soop;
}

/*
 * Function to load an icon set
 * This function returns the location
 * of the icons.
 */
function load_icons ($iconset = null) {
	if ($iconset == null || isset ($iconset) == false) {
		$iconset = 'lib/html/icons/mini_icons2/';
	}
	if (!file_exists ($iconset)) {
		die ("Error: icon set location does not exist in $iconset. Exiting.");
		exit (999);
	}
	return $iconset;
}

?>