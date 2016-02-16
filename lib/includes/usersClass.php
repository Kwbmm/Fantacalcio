<?php
	/**
	 * Class managing collecting all users
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	*/
	require_once 'dbClass.php';
	require_once 'userClass.php';
	require_once __DIR__.'/exceptions/SortException.php';

	class Users{
		private $users = array();

		function __construct($db=null){
			if(!isset($db))
				$db = DB::getInstance();
			$s = $db->query("SELECT UID FROM user");
			$results = $s->fetchAll(PDO::FETCH_ASSOC);
			foreach ($results as $row) {
				array_push($this->users, new User($row['UID'],$db));
			}
		}

		public function getUsers(){
			return $this->users;
		}

		/**
		 * Returns the users sorted by score in ascending or descending order
		 * @param  boolean $asc false for sorting in descending order, true otherwise
		 * @return array       Array of User objects
		 */
		public function getUsersByScore($asc=false){
			$cloned = $this->users;
			if($asc && !uasort($cloned,array($this,'sortByScoreAsc')))
				throw new SortException("Error sorting users by score");
			if(!$asc && !uasort($cloned,array($this,'sortByScoreDesc')))
				throw new SortException("Error sorting users by score");
			return $cloned;
		}

		/**
		 * Used by uasort() to sort the values
		 * @param  User $a A user object
		 * @param  User $b A user object
		 * @return int    -1 if $a < $b, 0 if $a === $b, 1 otherwise
		 */
		private function sortByScoreAsc($a,$b){
			if($a->getScore() === $b->getScore())
				return 0;
			return ($a->getScore() < $b->getScore()) ? -1 : 1;

		}

		/**
		 * Used by uasort() to sort the values
		 * @param  User $a A user object
		 * @param  User $b A user object
		 * @return int    -1 if $a > $b, 0 if $a === $b, 1 otherwise
		 */
		private function sortByScoreDesc($a,$b){
			if($a->getScore() === $b->getScore())
				return 0;
			return ($a->getScore() > $b->getScore()) ? -1 : 1;

		}

		public function getUserByUID($UID){
			foreach ($this->users as $index => $user) {
				if($user->getUserID() === $UID)
					return $user;
			}
		}

		/**
		 * Fetch one or more users by their name
		 * @param  string $username Input username
		 * @return array           Returns an array containing the matched users.
		 */
		public function getUserByUsername($username){
			$result = array();
			foreach ($this->users as $index => $user) {
				if($user->getUsername() === $username)
					array_push($result,$user);
			}
			return $result;
		}
	}
?>