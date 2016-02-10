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

		/**
		 * Default constructor used to initialize the Roster object.
		 * Each roster contains an array of SoccerPlayer objects.
		 * 
		 * @param int $UID The user ID, required to perform queries.
		 */
		function __construct($UID){
			$dbCls = new DB;
			$db=$dbCls->getDB();
            $stmt = $db->prepare("
            	SELECT sp.SPID,Name,Position,Team,Cost
            	FROM user_roster ur, soccer_player sp
            	WHERE UID=:uid
            	AND sp.SPID = ur.SPID");
            $stmt->bindValue(':uid',$UDI,PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $result) {
            	array_push($soccerPlayers, new SoccerPlayer(
            		$result['SPID'],
            		$result['Name'],
            		$result['Position'],
            		$result['Team'],
            		$result['Cost']));
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
				if($playerObj->getName === $pName)
					return $playerObj;
			}
			return null; //Player not found
		}
	}
?>