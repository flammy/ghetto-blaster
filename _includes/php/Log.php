<?php
	
/**
 * This class is responsible for logging
 *
 */
class Log {

	/**
	 * @var resource $log contains the filepointer for the file to log to
	 */
	var $log = null;
	
	
	/**
	 * @var string $file contains the path to the logfile
	 */
	var $file = null;
	
	
	/**
	 * @var bool $doLog switches logging on or off
	 */
	var $doLog = true;
	
	
	/*
	 * constructor method
	 */
	function __construct($doLog = null) {
		$this->doLog = $doLog;
		$this->file = dirname(__FILE__) . "/../txt/log.txt";
	}
	
	
	/** 
	 * This method logs to a given file if $doLog is true
	 * @var string $message contains the message to write into the logfile
	 *
	 */
	function logMessage($message) {
		
		// condition : log messages?
		if ($this->doLog) {
		
			// if file pointer doesn't exist, then open log file
			if (!$this->log) $this->logOpen();
		
			// define current time
			$time = date('Y-m-d H:i:s');
		
			$ip = $_SERVER['REMOTE_ADDR'];
			
			// condition : is a user set?
			$user = (isset($_SESSION['u'])) ? $_SESSION['u'] : "anon";
		
			// write current time, script name and message to the log file
			fwrite($this->log, $user . " (" . $ip . ") " . $message . " - " . $time . "\n");
		}
	}
	
	
	/** 
	 * This method returns the contents from the logfile 
	 * @return string contains the messages from the log file
	 *
	 */
	public function getLog() {
		return file($this->file);
	}
	
	
	/** 
	 * This method opens the logfile 
	 * @return resource contains the pile-pointer to the log file
	 *
	 */
	private function logOpen(){
		// define log file path and name
		$file = $this->file;
		
		// open log file for writing only; place the file pointer at the end of the file
		// if the file does not exist, attempt to create it
		$this->log = fopen($file, 'a') or exit("Can't open $file!");
	}
}