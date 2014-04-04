<?php
include_once ('lib/page_generator.php');
include_once ('lib/class_login.php');
/*$p = new page_generator ();*/
$l = new login ();
if (isset ($_GET['return-to'])) {
	$ref = urlencode ($_GET['return-to']);
} else {
	$ref = 'login.php';
}
if (isset ($_SERVER['HTTPS'])) {
	$loc = 'https://'.$_SERVER['SERVER_NAME'].'/'.$ref;
} else {
	$loc = 'http://'.$_SERVER['SERVER_NAME'].'/'.$ref;
}
/* Logout */
$l->l_session_stop ();
header ("location: $loc", 302);
exit (0);
?>