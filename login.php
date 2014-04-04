<?php
include_once ('lib/page_generator.php');
include_once ('lib/class_login.php');
$p = new page_generator ();
$l = new login ();

/* Login page */
$l->l_session_start ();
/* Referral URL */
if (isset ($_GET['return-to'])) {
	$ref = $_GET['return-to'];
} else {
	$ref = 'index.php';
}
if (isset ($_SERVER['HTTPS'])) {
	$loc = 'https://'.$_SERVER['SERVER_NAME'].'/'.$ref;
} else {
	$loc = 'http://'.$_SERVER['SERVER_NAME'].'/'.$ref;
}


/* If users are logged in, send them back where they came from */
if ($l->check_login () === true) {
	header ("location: $loc", 302);
	exit (0);
}

/* If the form was submitted $_POST['submit'] will be 1 */
if (isset ($_POST['submit'])) {
	if (!isset ($_POST['username']) || !isset ($_POST['password']) || $_POST['username'] == null || $_POST['password'] == null) {
		echo $p->g_login ($ref, 'Gebruikersnaam of wachtwoord niet ingevuld!');
		exit (0);
	}
	/* Check provided information */
	$username = $_POST['username'];
	$password = $_POST['password'];
	if ($l->l_login ($username, $password, 'username') === true) {
		/* Correctly logged in */
		header ("location: $loc", 302);
		exit (0);
	}
	/* Something was wrong */
	echo $p->g_login ($ref, 'Gebruikersnaam of wachtwoord is fout!');
	exit (0);
}

echo $p->g_login ($ref);
exit (0);

?>