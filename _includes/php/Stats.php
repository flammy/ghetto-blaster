<?php
/**
 * This class is responsible for generating stats
 *
 */

require_once('Log.php');


class Stats {
	

	/**
	 * @var object $log contains the logger object
	 */
	protected $log = null;
	
	/**
	 * @var array $statTypes containts a list of all types of stats
	 */
	protected $statTypes = array(
		'latest-tracks' => 'latestTracks',
		'top-tracks' => 'TopTracks',
		'top-users' => 'TopUsers',
		'top-tracks-by-user' => 'TopTracksByUser',
		'top-users-by-track' => 'TopUsersByTrack',
//		'plays-by-month' =>'TopTracks',
//		'top-tracks-by-month' => 'TopTracks',
//		'top-users-by-month' => 'TopTracks'
	);
	

	/**
	 * @var array $colourSchemes containts a list of all colour-shemes
	 */
	protected $colourSchemes = array(
		"family-and-friends",
		"health",
		"age",
		"food",
		"leisure",
		"love-and-sex",
		"money",
		"work"
	);
	

	/**
	 * @var array $colourSchemes containts a list of all graph types
	 */
	protected $graphTypes = array(
		"bar",
		"sbar",
		"line"
	);
	

	/**
	 * @var array $colourSchemes containts a list of all graph-orientations
	 */
	protected $graphOrientations = array(
		"v",
		"h",
	);
	

	/*
	 * Constructor
	 */
	public function __construct() {
		$log = new Log;
		$this->log = $log->getLog();
	}
	
	
	/**
	 * Gets a list of all stats types
	 * @return array contains a list of all stat types
	 */
	public function getStatTypes() {
		return $this->statTypes;
	}	
	
	
	/**
	 * Executs a method
	 * @var string $statType determines which mehtod gets executed
	 * @return mixed the result of the method
	 */
	public function getStats($statType) {
		// condition : if the requested stat type exists, run it
		if (isset($this->statTypes[$statType])) {
			$method = "get".$this->statTypes[$statType];
			return $this->$method();
		// requested method doesn't exist
		} else {
			return false;
		}
	}
	
	
	/**
	 * Gets the default stat type
	 * @return string contains the default stat type
	 */
	public function getDefaultStatType() {
		return key($this->statTypes);
	}
	
	
	/**
	 * Generates the flash-vars code block
	 * @var array $stats containts the stats
	 * @var string $cats contains the categories
	 * @var string $cats2 contains the categories
	 * @var string $title containts the title
	 * @return string contains html block with the flash vars
	 */
	protected function buildFlashVars($stats, $cats, $cats2, $title) {
		
		$chartData = array();
		
		
		// construct data
		foreach($stats as $key => $stat){
			$data = array(
				'name' => $key,
				'values' => array(),
			);
			//foreach($stats as $skey => $svalue){
				if (is_array($stat)) {
					$data['values'] = $stat;
				} else {
					$data['values'][] = $stat;
				}
			//}
			$chartData[] = $data;
		}		
		
		
		// select random styles
		$scheme = array_rand($this->colourSchemes);
		$graphType = array_rand($this->graphTypes);
		$orientation = array_rand($this->graphOrientations);
				
		// construct metadata
		$data = new stdClass();
		$data->title = $title;
		$data->id = '1';
		$data->type = $this->graphTypes[$graphType];
		$data->orient = $this->graphOrientations[$orientation];
		$data->pMode = false;
		$data->feliCat = $this->colourSchemes[$scheme];
		$data->cats = $cats;
		$data->cats2 = $cats2;
		$data->chartData = $chartData;
		$data->dSep = '.';
		$data->outOf = null;
		
		$json = urlencode(json_encode($data));
		
		
		
		return '
				<div id="graph"></div>
				
				<script> 
					var flashvars = { data: "'.$json.'" };

					var params = { 
					  bgcolor: "#ffffff"
					};
					swfobject.embedSWF("../_includes/swf/graphinator.swf", "graph", "410", "700", "9.0.115.0", null, flashvars, params);
				</script>
		';
	}
	
	
	
	
	
	
	
	/**
	 * Makes a graph of the top tracks
	 * @return string contains the html code with the flash vars for this stat
	 */
	protected function getTopTracks() {
		$tracks = array();
		
		// loop through all played tracks
		foreach($this->log as $lineNum => $line) {
			
			// if the line refers to playing a track
			if (strpos($line, "played the file '") !== false) {
			
				// track is after `played the file '` until closing `'`
				$start = strpos($line, "played the file '")+17;
				$end = strpos($line, "' - ") - $start;
				$track = substr($line, $start, $end);
			
				// condition : if track has already been added to the array, add count
				if (array_key_exists($track, $tracks)) {
					$tracks[$track]++;

				// new track, add to array
				} else {
					$tracks[$track] = 1;
				}
			}
		}
		
		arsort($tracks);
		$tracks = array_slice($tracks, 0, 10);
		$categories = array('Top Tracks');
		$categories2 = array(null);


		return $this->buildFlashVars($tracks, $categories, $categories2, 'Top Tracks');
	}
	
	
	
	/**
	 * Makes a graph of the top users
	 * @return string contains the html code with the flash vars for this stat
	 */
	protected function getTopUsers() {
		$users = array();

		// loop through all played tracks
		foreach($this->log as $lineNum => $line) {

			// get username - it's the start of the line until the first space
			$user = substr($line, 0, strpos($line, ' '));

			// condition : if user has already been added to the array, add count
			if (array_key_exists($user, $users)) {
				$users[$user]++;

			// new user, add to array
			} else {
				$users[$user] = 1;
			}
		}

		arsort($users);
		$users = array_slice($users, 0, 10);
		$categories = array('Top Users');
		$categories2 = array(null);


		return $this->buildFlashVars($users, $categories, $categories2, 'Top Users');
	}
	
	
	
	/**
	 * Makes a graph of the latest tracks
	 * @return string contains the html code with the flash vars for this stat
	 */
	protected function getLatestTracks() {

		$tracks = "";
		
		$log = array_reverse($this->log);
		$log = array_slice($log, 0, 30);
		
		
		// loop through all played tracks
		foreach($log as $lineNum => $line) {
			
			// if the line refers to playing a track
			if (strpos($line, "played the file '") !== false) {
			
				// track is after `played the file '` until closing `'`
				$start = strpos($line, "played the file '")+17;
				$end = strpos($line, "' - ") - $start;
				$track = substr($line, $start, $end);
				$track = str_replace('/sfx', '', $track);
				$track = str_replace('/', ' / ', $track);
				$user = substr($line, 0, strpos($line, ' '));
				$date = date('g:ia, l jS F Y', strtotime(substr($line, strpos($line, "' - ")+4)));
				
				$tracks .= '
					<p class="track">
						<strong>' . $track . '</strong>
						<em>' . $user . '</em>
						<span>' . $date . '</span>
					</p>';
			}
		}
		
		return $tracks;		
	}

	
	
	
	/**
	 * Makes a graph of the top users by track
	 * @return string contains the html code with the flash vars for this stat
	 */
	protected function getTopUsersByTrack() {
		
		$limit = 7;
		
		
		$tracks = array();
		
		// loop through all played tracks
		foreach($this->log as $lineNum => $line) {
			
			// if the line refers to playing a track
			if (strpos($line, "played the file '") !== false) {
			
				// track is after `played the file '` until closing `'`
				$start = strpos($line, "played the file '")+17;
				$end = strpos($line, "' - ") - $start;
				$track = substr($line, $start, $end);
				
			
				// condition : if track has already been added to the array, add count
				if (array_key_exists($track, $tracks)) {
					$tracks[$track]++;

				// new track, add to array
				} else {
					$tracks[$track] = 1;
				}
			}
		}
		
		arsort($tracks);
		$tracks = array_slice($tracks, 0, $limit);
		$categories = array_keys($tracks);
		$categories2 = array();
		
		$pad = count($tracks);
		if ($pad > $limit) { $pad = $limit; }
		
		$categories2 = array_pad($categories2, $pad, null);

		// got tracks, start on users
		$users = array();


		// loop through all played tracks
		foreach($this->log as $lineNum => $line) {
			
			// if the line refers to playing a track
			if (strpos($line, "played the file '") !== false) {
			
				// track is after `played the file '` until closing `'`
				$start = strpos($line, "played the file '")+17;
				$end = strpos($line, "' - ") - $start;
				$track = substr($line, $start, $end);
				$user = substr($line, 0, strpos($line, ' '));
				
				// if track is one of the top ones
				if (in_array($track, $categories)) {

					// if user doesn't exist, start them
					if (!array_key_exists($user, $users)) {
						$users[$user] = array();
						$users[$user] = array_pad($users[$user], $pad, 0);						
					}
					
					// get the track position in the categories array
					$key = array_search($track, $categories);
					$users[$user][$key]++;
				}
			}
		}
		
		$users = array_slice($users, 0, $limit);
		
		//
		return $this->buildFlashVars($users, $categories, $categories2, 'Top Tracks By User');
	}
	
	
	/**
	 * Makes a graph of the top tracks by user
	 * @return string contains the html code with the flash vars for this stat
	 */
	protected function getTopTracksByUser() {
		
		$limit = 5;
		
		$users = array();
		
		// loop through all played tracks
		foreach($this->log as $lineNum => $line) {
			
			// if the line refers to playing a track
			if (strpos($line, "played the file '") !== false) {
			
				$user = substr($line, 0, strpos($line, ' '));
				if (array_key_exists($user, $users)) {
					$users[$user]++;
				} else {
					$users[$user] = 1;
				}
			}
		}
		
		arsort($users);
		$users = array_slice($users, 0, $limit);
		$categories = array_keys($users);
		$categories2 = array();
		
		$pad = count($users);
		if ($pad > $limit) { $pad = $limit; }
		
		$categories2 = array_pad($categories2, $pad, null);

		// got users, start on tracks
		$tracks = array();


		// loop through all played tracks
		foreach($this->log as $lineNum => $line) {
			
			// if the line refers to playing a track
			if (strpos($line, "played the file '") !== false) {
			
				// track is after `played the file '` until closing `'`
				$start = strpos($line, "played the file '")+17;
				$end = strpos($line, "' - ") - $start;
				$track = substr($line, $start, $end);
				$user = substr($line, 0, strpos($line, ' '));
				
				// if track is one of the top ones
				if (in_array($user, $categories)) {

					// if user doesn't exist, start them
					if (!array_key_exists($track, $tracks)) {
						$tracks[$track] = array();
						$tracks[$track] = array_pad($tracks[$track], $pad, 0);						
					}
					
					// get the track position in the categories array
					$key = array_search($user, $categories);
					$tracks[$track][$key]++;
				}
			}
		}
		
		// compare the total values for the array to find the top tracks
		function cmp($a, $b) {
			$sumA = 0;
			$sumB = 0;
			foreach($a as $aVal) {
				$sumA += $aVal;
			}
			foreach($b as $bVal) {
				$sumB += $bVal;
			}
			if ($sumA == $sumB) {
				return 0;
			} else {
				return ($sumA < $sumB) ? 1 : -1;
			}
		}
		
		
		uasort($tracks, 'cmp');
		$tracks = array_slice($tracks, 0, $limit);
		
		//
		return $this->buildFlashVars($tracks, $categories, $categories2, 'Top Users By Track');
	}
}