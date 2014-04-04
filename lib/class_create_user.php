<?php
include_once ('mysql_connect.php');
include_once ('class_login.php');
if (!function_exists ('password_verify')) {
	require_once ('class_password_hash.php');
}
class create_user extends login {

	public $user_exists = false;

	/*
	 * Function to create a salt (not for passwords! that's done by password_hash)
	 * @return string $salt
	 */
	protected function mk_salt () {
		return bin2hex (openssl_random_pseudo_bytes (16));
	}

	/*
	 * Function to create a user
	 * @param string $username
	 * @param string $email
	 * @param string $password
	 * @return true/false
	 */
	public function create ($username, $email, $password) {
	/*openssl_random_pseudo_bytes() */
		/* Check whether username exists */
		if ($this->get_user_id ($username, 'username') != false) {
			/* Exists */
			$this->user_exists = true;
			return false;
		}
		$q = "INSERT INTO users (username, password, email, salt) VALUES (?, ?, ?, ?)";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('ssss', $username, password_hash ($password, PASSWORD_DEFAULT), $email, $this->mk_salt ());
		$st->execute () or die ($st->error);
		$st->close ();
		$st = null;
		/* Get user id */
		$uid = $this->get_user_id ($username, 'username');
		if ($uid === false) {
			/* Something went wrong */
			return false;
		}
		$q = "INSERT INTO login_attempts (uid, last_login, last_attempt, failed_attempts) VALUES (?, ?, ?, ?)";
		$now = date ('c');
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$o = 0;
		$st->bind_param ('dsss', $uid, $now, $now, $o);
		$st->execute () or die ($st->error);
		$st->close ();
		return true;
	}
}

?>