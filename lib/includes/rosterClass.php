<?php
	/**
	 * Class managing roster of each user.
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	 */

	require_once 'dbClass.php';
	require_once 'soccerPlayerClass.php';

	class Roster {
		private $soccerPlayers=array();
		private $db = null;

		/**
		 * Default constructor used to initialize the Roster object.
		 * Each roster contains an array of SoccerPlayer objects.
		 * 
		 * @param int $UID The user ID, required to perform queries.
		 */
		function __construct($UID,$db=null){
			if(!isset($db))
				$this->db = DB::getInstance();
			else
				$this->db = $db;
            $stmt = $this->db->prepare("
            	SELECT sp.SPID,Name,Position,Team,Cost
            	FROM user_roster ur, soccer_player sp
            	WHERE UID=:uid
            	AND sp.SPID = ur.SPID");
            $stmt->bindValue(':uid',$UID,PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $result) {
            	array_push($this->soccerPlayers, SoccerPlayer::consFromFull(
            		$result['SPID'],
            		$result['Name'],
            		$result['Position'],
            		$result['Team'],
            		$result['Cost'],$this->db));
            }
		}

		public function getPlayers(){
			return $this->soccerPlayers;
		}

		/**
		 * Returns the player object, search by name
		 * @param  string $pName The name of the soccer player
		 * @return SoccerPlayer  Returns the SoccerPlayer object or null if not found
		 */
		public function getPlayer($pName){
			foreach ($this->soccerPlayers as $playerObj) {
				if(strpos($playerObj->getName(),$pName) !== false)
					return $playerObj;
			}
			return null; //Player not found
		}
	}
?>