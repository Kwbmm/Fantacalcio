<?php
	/**
	 * Class managing soccer player.
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	 */
	require_once 'dbClass.php';

	class SoccerPlayer {
		private $spid=null, $name=null, $pos=null, $team=null, $cost=null;		
		
		//Constructors

		public static function consFromFull($spid,$name,$pos,$team,$cost){
			$this->spid = $spid;
			$this->name = $name;
			$this->pos = $pos;
			$this->team = $team;
			$this->cost = $cost;

		}

		public static function consFromSPID($spid){
			$dbCls = new DB('root','','fantacalcio','localhost');
			

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
	}
?>