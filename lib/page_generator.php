<?php

include_once ('html_generator.php');

class page_generator {

	protected $skin;
	protected $iconset;

	function __construct ($skin = null, $iconset = null) {
		$this->skin = include_skin ($skin);
		$this->iconset = load_icons ($iconset);
	}

	/*
	 * Create a login page
	 * @param string $referrer
	 * @param string $message
	 * @return string $page
	 */
	public function g_login ($referrer = null, $message = null) {
		$login_wrapper = '<div class="login-wrapper">
	%s
</div>';
		$login_form = '<form class="login-form" id="login-form" method="post" action="login.php?return-to=%s">
	%s
	<div class="spacer"></div>
	<div class="login-form username"><img src="'.$this->iconset.'person.gif'.'" class="login-form icon" alt="Gebruiker" /><label for="username">%s</label>&nbsp;<input type="text" id="username" name="username" class="login-form" /></div>
	<div class="spacer"></div>
	<div class="login-form password"><img src="'.$this->iconset.'key.gif'.'" class="login-form icon" alt="Wachtwoord" /><label for="password">%s</label>&nbsp;<input type="password" id="password" name="password" class="login-form" /></div>
	<div class="login-form submit"><input type="hidden" name="submit" value="1" /><input type="submit" value="Aanmelden" /></div>
</form>';
		/* $this->lang->string ('username') */
		if ($referrer === null) {
			$referrer = 'index.php';
		}
		$referrer = urlencode ($referrer);
		$m = '<!-- Login form -->';
		if ($message != null) {
			$m = '<div class="login-form message"><img src="'.$this->iconset.'warning.gif'.'" class="login-form icon" alt="Message" /><span class="message">'.htmlentities ($message).'</span></div>';
		}
		$lc = sprintf ($login_wrapper, sprintf ($login_form, $referrer, $m, 'Gebruikersnaam', 'Wachtwoord'));
		return $this->skin->create_base_page ('Aanmelden', $lc); 
	}
}

?>