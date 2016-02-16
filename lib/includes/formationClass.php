<?php
	/**
	 * Class managing the formation of a user of a particular match day
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	*/
	require_once 'soccerPlayerClass.php';
	class Formation	{		
		private $mid = null;
		private $players = array();

		private function __construct($mid,$players) {
			$this->mid = $mid;
			$this->players = $players;
		}
		/**
		 * Constructs a Formation object, called from a User object
		 * It will contain only the formations of the user
		 *
		 * @param  int 			$mid     	Match ID
		 * @param  array 		$players 	Array made of index => array('disposition'=>'value', 'SPID'=>'value')
		 * @return Formation				Returns the formation
		 */
		public static function consFromUser($mid,$players,$db=null){				
			$arrayPlayer = array();
			foreach ($players as $pIndex => $values)
				$arrayPlayer[$values['disposition']] = SoccerPlayer::consFromSPID($values['SPID'],$db);
			return new Formation($mid,$arrayPlayer);
		}

		public function getPlayers(){
			return $this->players;
		}

		public function getMID(){
			return $this->mid;
		}
	}
?>