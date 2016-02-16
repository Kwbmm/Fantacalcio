<?php
	/**
	 * Class managing soccer player.
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	 */
	require_once 'dbClass.php';
	require_once __DIR__.'/exceptions/SoccerPlayerException.php';

	class SoccerPlayer {
		private $spid=null, $name=null, $pos=null, $team=null, $cost=null;
		private $db=null;
		private $scores = array();
		private static $instance=null;	
		
		private function __construct($spid,$name,$pos,$team,$cost){
			$this->spid = $spid;
			$this->name = $name;
			$this->pos = $pos;
			$this->team = $team;
			$this->cost = $cost;
		}

		/**
		 * Constructs a SoccerPlayer object: does not require fetching any paramter from DB as all the data is already provided
		 * @param  int $spid Soccer Player ID
		 * @param  string $name Soccer Player Name
		 * @param  string $pos  Soccer Player Position
		 * @param  string $team Soccer Player Team
		 * @param  int $cost Soccer Player Cost
		 * @param  PDO::Database $db An optional database, otherwise default DB settings are used
		 * @return SoccerPlayer       The created Soccer Player Object
		 */
		public static function consFromFull($spid=null,$name=null,$pos=null,$team=null,$cost=null,$db=null){
			if(!isset($db))
				$db = DB::getInstance();
			if(!isset($spid) || !isset($name) || !isset($name) || !isset($pos) || !isset($team) || !isset($cost))
				throw new SoccerPlayerException("Some parameter is null");
			self::$instance = new SoccerPlayer($spid,$name,$pos,$team,$cost); 
			self::$instance->db = $db;
			return self::$instance;
		}

		/**
		 * Constructs a Soccer Player Object starting from the SPID. Takes an optional DB in case of customization needs.
		 * @param  int $spid Soccer Player ID
		 * @param  PDO::Database $db   A (optional) database in case the default one is not an option
		 * @return SoccerPlayer       The created Soccer Player Object
		 */
		public static function consFromSPID($spid,$db=null){
			if(!isset($db))
				$db = DB::getInstance();
			$s = $db->prepare("SELECT Name,Position,Team,Cost
            	FROM soccer_player
            	WHERE SPID=:spid");
			$s->bindValue(':spid',$spid,PDO::PARAM_INT);
			$s->execute();
			if($s->rowCount() !== 1)
				throw new PDOException("Expected row (1), got row (".$s->rowCount().")");
			$result = $s->fetch(PDO::FETCH_ASSOC);
			self::$instance = new SoccerPlayer($spid,$result['Name'],$result['Position'],$result['Team'],$result['Cost']);
			self::$instance->db = $db;
			return self::$instance;
		}

		public function getSPID() {
			return $this->spid;
		}

		public function getName() {
			return $this->name;
		}

		public function getPosition(){
			return $this->pos;
		}

		public function getTeam(){
			return $this->team;
		}

		public function getCost(){
			return $this->cost;
		}

		public function getScores(){
			if(empty($this->scores))
				$this->setScores();
			return $this->scores;
		}

		public function getScore($mid){
			if(empty($this->scores))
				$this->setScores();
			return $this->scores[$mid];
		}

		private function setScores(){
			$s = $this->db->prepare("SELECT mid, mark FROM player_mark WHERE spid=:spid");
			$s->bindValue(':spid',$this->spid, PDO::PARAM_INT);
			$s->execute();
			$results = $s->fetchAll(PDO::FETCH_ASSOC);
			foreach ($results as $row)
				$this->scores[$row['mid']] = $row['mark'];
		}
	}
?>