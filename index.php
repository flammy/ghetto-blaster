<?php
	// import ghetto blaster files
	require_once("./_includes/php/Login.php");
	require_once("./_includes/php/GhettoBlaster.php");
	require_once("./_includes/php/PageBuilder.php");

	// start the page builder
	$page = new PageBuilder();
	
	// get ghetto blaster configuration file
	$conf = parse_ini_file("./config.ini.php", true);

	// condition : log-in required?
	if ($conf['options']['doLogin']) {
		session_start();

		// condition : if not currently logged in, show form
		$login = doLogin($conf['users']);
		if (!$login) {
			echo $page->buildLoginPage();
			exit;
		}
	} 

	// start the ghetto blaster
	$ghettoBlaster = new GhettoBlaster;

	
	// Configure ghetto blaster with values from config.ini.php
	$ghettoBlaster->setPath($conf['paths']['sfx']);
	$ghettoBlaster->setEnv($conf['system']['env']);
	$ghettoBlaster->setPlayer($conf['system']['player']);
	$ghettoBlaster->setTts($conf['system']['tts']);
	$ghettoBlaster->setDebug($conf['system']['debug']);


	// list sounds
	$files = $ghettoBlaster->createFileList();

	// build page
	echo $page->buildPage($files, $ghettoBlaster->volume);
