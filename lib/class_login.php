<?php

include_once ('mysql_connect.php');
if (!function_exists ('password_verify')) {
	require_once ('class_password_hash.php');
}

class login extends db_connect {

	public $failed_attempt;

/* http://www.wikihow.com/Create-a-Secure-Login-Script-in-PHP-and-MySQL */
	public function l_session_start () {
		$session_name = 'pitah';
		$httponly = true;
		if (ini_get ('session.use_only_cookies') != 1) {
			die ("Error: use_only_cookies is set to 0");
		}
		$ck = session_get_cookie_params ();
		session_set_cookie_params (
			$ck['lifetime'],
			$ck['path'],
			$ck['domain'],
			false,
			true
		);
		session_name ($session_name);
		session_start ();
		session_regenerate_id ();
		return true;
	}

	/*
	 * Function to destroy the session
	 * (in effect a logout-function
	 * @return true/false
	 */
	public function l_session_stop () {
		$this->l_session_start ();
		$_SESSION = array (); /* Unsets all session values */
		$ck = session_get_cookie_params ();
		/* Delete cookie */
		setcookie (session_name (), '', time () - 3600,
					$ck['path'],
					$ck['domain'],
					false,
					true
		);
		/* Destroy session */
		session_destroy ();
		return true;
	}

	/*
	 * Function to set the session hash
	 * to $hash
	 * @param int $uid
	 * @param string $hash
	 * @return string $hash
	 */
	protected function set_session_hash ($uid, $hash) {
		$q = "UPDATE users SET session_hash = ? WHERE id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('sd', $hash, $uid);
		$st->execute () or die ($st->error);
		$st->close ();
		return $hash;
	}

	/*
	 * Function to return the salt from a user (this has nothing to do with the password salt)
	 * @param int $uid
	 * @return string $salt
	 */
	protected function salt ($uid) {
		$q = "SELECT salt FROM users WHERE id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('d', $uid);
		$st->execute () or die ($st->error);
		$st->bind_result ($salt);
		if (!$st->fetch ()) {
			/* ID is not in the table */
			die ("Error: user $uid does not exist.");
			return false;
		}
		return $salt;
	}

	/*
	 * Function to compare the session hash $hash with the stored hash
	 * Steps:
	 *	Search for the hash in the table
	 *	Compare the resulting uid with the uid as provided
	 *	return true or false
	 * @param string $hash
	 * @param string $uid_hash
	 * @return $uid/false
	 */
	protected function compare_hash ($h, $uid_h) {
		//hash ('sha512', $uid);
		$q = "SELECT id FROM users WHERE session_hash = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $h);
		$st->execute () or die ($st->error);
		$st->bind_result ($uid);
		if (!$st->fetch ()) {
			/* Hash is not in the table */
			return false;
		}
		if (hash ('sha512', $uid) != $uid_h) {
			/* Hash is in the table, but uid doesn't match */
			return false;
		} else {
			return $uid;
		}
		return false;
	}

	/*
	 * Function to reset the failed_attempts column in login_attempts
	 * @param int $uid
	 * @return true/false
	 */
	protected function reset_attempts ($uid) {
		$q = "UPDATE login_attempts SET failed_attempts = 0 WHERE uid = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		/*YYYY-MM-DD HH:MM:SS*/
		$st->bind_param ('d', $uid);
		$st->execute () or die ($st->error);
		$st->close ();
		return true;
	}

	/*
	 * Function to check the amount of attempts the user has
	 * (only when last_attempt is later than last_login this is
	 * checked)
	 * @param int $uid
	 * @return int $amount
	 */
	protected function login_attempts ($uid) {
		$q = "SELECT last_login, last_attempt, failed_attempts FROM login_attempts WHERE uid = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('d', $uid);
		$st->execute () or die ($st->error);
		$st->bind_result ($last_login, $last_attempt, $failed_attempts);
		if (!$st->fetch ()) {
			die ("Error: user $uid does not exist!");
			return false;
		}
		$st->close ();
		if (strtotime ($last_login) < strtotime ($last_attempt)) {
			/* If the user has made an attempt after he was logged in, check attempts, otherwise reset attempts
			'cause they don't really count anymore */
			return $failed_attempts;
		} else {
			$this->reset_attempts ($uid);
			return 0;
		}
	}

	/*
	 * Function to add a failed login attempt
	 * @param int $uid
	 * @return true/false
	 */
	protected function add_login_attempts ($uid) {
		return $this->update_last_login ($uid, true);
	}

	/*
	 * Function to update the login_attempts table
	 * @param int $uid
	 * @param bool $failed - add a failed login-attempt (otherwise just update the time)
	 * @return $last_login
	 */
	protected function update_last_login ($uid, $failed = false) {
		if ($failed == true) {
			$q = "UPDATE login_attempts SET last_attempt = ?, failed_attempts = (failed_attempts + 1) WHERE uid = ?";
		} else {
			$q = "UPDATE login_attempts SET last_login = ? WHERE uid = ?";
		}
		$st = $this->c->prepare ($q) or die ($this->c->error);
		/*YYYY-MM-DD HH:MM:SS*/
		$now = date ('c');
		$st->bind_param ('sd', $now, $uid);
		$st->execute () or die ($st->error);
		$st->close ();
		return $now;
	}

	/*
	 * Function to get the last_login
	 * @param int $uid
	 * @return dt $last_login
	 */
	protected function last_login ($uid) {
		$q = "SELECT last_login FROM login_attempts WHERE uid = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('d', $uid);
		$st->execute ();
		$st->bind_result ($last_login);
		if ($st->fetch () === true) {
			$st->close ();
			return $last_login;
		} else {
			$st->close ();
			die ("Error: user $uid does not exist.");
			return false;
		}
	}
	
	/*
	 * Function to check the password
	 * @param int $user_id
	 * @param string $password
	 * @return true/false
	 */
	protected function check_password ($uid, $pw) {
		$q = "SELECT password FROM users WHERE id = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('d', $uid);
		$st->execute ();
		$st->bind_result ($hash);
		if ($st->fetch () === true) {
			$st->close ();
			if (password_verify ($pw, $hash) === true) {
				return true;
			} else {
				return false;
			}
		} else {
			$st->close ();
			return false;
		}
	}

	/*
	 * Function to get the ID of a user
	 * @param string $identifier
	 * @param string $column
	 * @return int $uid
	 */
	public function get_user_id ($identifier, $column) {
		$column = $this->c->real_escape_string ($column);
		$q = "SELECT id FROM users WHERE ".$column." = ?";
		$st = $this->c->prepare ($q) or die ($this->c->error);
		$st->bind_param ('s', $identifier);
		$st->execute ();
		$st->bind_result ($uid);
		if ($st->fetch () === true) {
			$st->close ();
			return $uid;
		} else {
			$st->close ();
			return false;
		}
	}

	/*
	 * Function to login
	 * @param string $identifier
	 * @param string $password
	 * @param string $id_type = null (default: username | values: username)
	 * @return true/false
	 */
	public function l_login ($identifier, $password, $identifier_type) {
		if ($identifier_type == 'username') {
			$uid = $this->get_user_id ($identifier, 'username');
			if ($uid == false) {
				return false;
			}
		}/* elseif ($identifier_type == 'email') {
			$uid = $this->get_user_id ($identifier, 'email');
			if ($uid == false) {
				return false;
			}
		}*/ else {
			die ("Error: \$identifier_type is not any of the accepted values.");
			return $false;
		}
		if ($this->login_attempts ($uid) > 3) {
			sleep (10); /* Annoy the user by sleeping for 10 seconds */
			$this->reset_attempts ($uid);
		}
		if ($this->check_password ($uid, $password) === true) {
			/* Log-in success */
			$this->failed_attempt = false;
			$last_login = $this->update_last_login ($uid);
			$salt = $this->salt ($uid); /* Fake salt from DB - random*/
			$browser = $_SERVER['HTTP_USER_AGENT'];
			$ip = $_SERVER['REMOTE_ADDR'];
			$_SESSION['user'] = hash ('sha512', $uid);
			$s_hash = hash ('sha512', $uid.$last_login.$browser.$ip.$salt);
			$_SESSION['login'] = $s_hash;
			$this->set_session_hash ($uid, $s_hash);
			return true;
		} else {
			$this->failed_attempt = true;
			$this->add_login_attempts ($uid);
			return false;
		}
		return false;
	}

	/*
	 * Function to check session variables whether
	 * a user is logged in
	 * @return true/false
	 */
	public function check_login () {
		if (!isset ($_SESSION['user']) || !isset ($_SESSION['login'])) {
			return false;
		}
		$uid_h = $_SESSION['user'];
		$hash = $_SESSION['login'];
		if ($this->compare_hash ($hash, $uid_h) === false) {
			/* If this succeeds, we know the the combination of $uid_h & $hash is valid */
			return false;
		}
		$uid = $this->compare_hash ($hash, $uid_h);
		$browser = $_SERVER['HTTP_USER_AGENT'];
		$ip = $_SERVER['REMOTE_ADDR'];
		$last_login = $this->last_login ($uid);
		$salt = $this->salt ($uid);
		$ck_hash = hash ('sha512', $uid.$last_login.$browser.$ip.$salt);
		/* If below succeeds, we know that the session variables correspond with the DB variables */
		if ($ck_hash != $hash) {
			return false;
		} else {
			return true;
		}
		return false;
	}
	
}
?>