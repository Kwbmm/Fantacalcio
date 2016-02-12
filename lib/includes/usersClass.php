<?php
	/**
	* 
	*/

	require_once 'dbClass.php';
	require_once 'userClass.php';

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