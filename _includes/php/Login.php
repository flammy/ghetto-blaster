<?php
/**
 * This File provides the doLogin function
 */



	/** 
	 * This function generates a login session if username and password matches
	 * @var array $login contains login and password array('login' => '', 'password' => '')
	 * @return bool contains the result of the login check
	 */
	function doLogin($login) {

		// condition : if form is posted, assess it's validity
		if (isset($_POST) && count($_POST) > 0) {
			foreach($login as $username => $password) {
				if ($_POST['u'] == $username && $_POST['p'] == $password) {
					$_SESSION['u'] = $_POST['u'];
					$_SESSION['p'] = $_POST['p'];
					return true;
				}
			}
		}

		// condition : if logged in already, double-check...
		if (isset($_SESSION) && isset($_SESSION['u']) && isset($_SESSION['p'])) {
			foreach($login as $username => $password) {
				if ($_SESSION['u'] == $username && $_SESSION['p'] == $password) {
					return true;
				}
			}
		}

		return false;
	}