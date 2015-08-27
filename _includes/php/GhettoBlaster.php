<?php
/**
 * This class is responsible for the main functionality of the Ghetto Blaster
 *
 */
class GhettoBlaster {
	/**
	 * @var string $env contains the enviroment variables to set before executing an command
	 * use setEnv() to set this variable from outside of this class
	 */

	var $env = "";

	/**
	 * @var string $player contains path to the mediaplayer binary
	 * use setPlayer() to set this variable from outside of this class
	 */
	var $player = "";


	/**
	 * @var string $tts contains path to the tts binary
	 * use setTts() to set this variable from outside of this class
	 */
	var $tts ="";

	/**
	 * @var string $debug contains the debug string, which is appended to the executed command.
	 * use setTts() to set this variable from outside of this class
	 */
	var $debug = "";

	/**
	 * @var string $path contains the path to the sound effects
	 * use setPath() to set this variable from outside of this class
	 */
	var $path = "/";
	
	
	/**
	 * @var string $volume contains the current volume
	 * use volumeUp() / volumeDown() to change this variable from outside of this class
	 */
	var $volume = 0;
	
	/**
	 * @var string $symlink contains the name of the symlink, to point to $path
	 * This is set by setPath()
	 */
	var $symlink = "sfx";
	
	
	/**
	 * @var array $ignorelist contains a list of filenames & directory names to ignore
	 * This cannot be configured from outside of the class.
	 */
	var $ignorelist = array (
		'.',
		'..',
		'.svn',
		'CVS',
		'.DS_Store',
		'_htaccess',
		'.htaccess',
		'_htpasswd',
		'.htpasswd',
		'Thumbs.db'
	);
	
	
	/**
	 * @var array $playableFiletypes contains a list of file types that are playable
	 * This cannot be configured from outside of the class.
	 * 
	 */
	var  $playableFiletypes = array (
		'mp3',
		'mp4',
		'wav',
		'aiff',
		'm4a'
	);
	

	/*
	 * Constructor method
	 */
	function __construct() {
		$this->getVolume();
	}
	
	
	/** 
	 * This method sets the path for the sound-effects 
	 * If no symlink to $symlink exists it creates a symlink to $path
	 * @var string $path contains the path to the folder containing the sound-effects
	 *
	 */
	function setPath($path) {
		$this->path = $path;
		
		// condition : does symlink exist?
		if (!@readlink($this->symlink)) {
			@symlink($path, $this->symlink);
		}
	}

	/**
	 * This Method sets the enviroment variables which are set before executing an commant
	 * @var string $env contains the enviroment variables
	 */
	function setEnv($env) {
		$this->env = $env;
	}


	/**
	 * This Methode sets the Path to the mediaplayer binary
	 * @var string $player containts the path to the mediaplayer binary
	 */
	function setPlayer($player) {
		$this->player = $player;
	}


	/**
	 * This Methode sets the Path to the tts binary
	 * @var string $tts containts the path to the tts binary
	 */
	function setTts($tts) {
		$this->tts = $tts;

	}


	/**
	 * This Methode sets debug string, wich is appended after the command
	 * It is used to pipe all output from binary to /dev/null, 
	 * Use this not only to prevent output but also to detach the programm from the apache process (nonblocking)
	 * @var string $tts containts the debug string
	 */
	function setDebug($debug) {
		$this->debug = $debug;
	}
	
	
	/**
	 * This Methode executes the mediaplayer with the given file
	 * @var string $play containts the mediafile to play 
	 * @return string $cmd contains the output from the player if $debug is set to ''
	 */
	function play($play) {
		$play = strip_tags($play);
		$play = str_replace('/sfx', '', $play);
		$cmd = $this->env.'  '.$this->player.' "' . $this->path . $play.'"  '.$this->debug;
		shell_exec($cmd);
		return $cmd;
	}
	
	
	/**
	 * This Methode executes killall command for mediaplayer and tts
	 * @return string $cmd contains the output from the player if $debug is set to ''
	 */
	function stop() {
		$cmd1 = $this->env.' killall '.$this->player.' '.$this->debug;
		$cmd2 = $this->env.' killall '.$this->tts.' '.$this->debug;
		$ret = shell_exec($cmd1);
		$ret = shell_exec($cmd2);
		return $cmd1.cmd2;
	}
	
	
	/**
	 * This Methode gets the current system volume
	 * @return string $volume the current system volume
	 */
	function getVolume() {
		$this->volume = shell_exec('osascript -e "output volume of (get volume settings)"');
		return array("volume" => $this->volume);
	}
	

	/**
	 * This Methode sets the system volume to 0
	 * @return string $volume the current system volume
	 */
	function mute() {
		shell_exec("osascript -e 'set volume output volume 0'");
		return $this->getVolume();
	}

	
	/**
	 * This Methode increases the system volume
	 * @return string $volume the current system volume
	 */
	function volumeUp() {
		shell_exec("osascript -e 'set volume output volume (get (output volume of (get volume settings)) + 5)'");
		return $this->getVolume();
	}
	
	
	
	/**
	 * This Methode decreases the system volume
	 * @return string $volume the current system volume
	 */
	function volumeDown() {
		shell_exec("osascript -e 'set volume output volume (get (output volume of (get volume settings)) - 5)'");
		return $this->getVolume();
	}
	
	
	
	/**
	 * This Methode executes the tts with the given text and voice
	 * @var string $txt containts the text to play
	 * @var string $play containts voice of the text
	 * @return string $cmd contains the output from the player if $debug is set to ''
	 */
	function say($txt, $voice) {
		$cmd = $this->env.' '.$this->tts.' -v '.$voice.' "'. $txt . '" '.$this->debug;
		shell_exec($cmd);
		return $cmd;
	}
	
	

	/**
	 * Create a list of all files
	 * @var string $path  to search for sound effects 
	 * @var string $dir contains the current directory name in recursive mode
	 * @return array $fileList Array of all files, with file/directory details
	 * @access public
	 */
	function createFileList($path="", $dir="") {
		
		if (empty($path)) {
			$path = $this->path;
			$root = true;
		} else {
			$root = false;
		}

		// temporary arrays to hold separate file and directory content
		$filelist = array();
		$directorylist = array();

		// get the ignore list, in local scope (can't use $this-> later on)
		$ignorelist = $this->ignorelist;

		// Open directory and read contents
		if (is_dir($path)) {

			// loop through the contents
            $dirContent = scandir($path);

			foreach($dirContent as $key => $file) {

				// skip over any files in the ignore list, and mac-only files starting with ._
				if (!in_array($file, $ignorelist) && (strpos($file, "._") !== 0)) {

					// condition : if it is a directory, add to dir list array
					if (is_dir($path.$file)) {

						$directorylist[$file] = array(
							"files" => $this->createFileList($path.$file, $file)
						);

                    // file, add to file array
					} else {
						if ($root) {
							$directorylist["root"]["files"][] = array(
								"file" => $file,
								"path" => $path,
								"dir" => $dir
							);
						} else {
							$filelist[] = array(
								"file" => $file,
								"path" => $path,
								"dir" => $dir . "/"
							);
						}
					}
				}
			}
		}

		// merge file and directory lists
		$finalList = array_merge($directorylist, $filelist);
		return $finalList;
	}
}
