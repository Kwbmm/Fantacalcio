<?php 
	/**
	 * Class managing initialization of DB.
	 * Class uses PDO.
	 * 
	 * @author Marco Ardizzone
	 * @link //twitter.com/marcoardiz
	 */
	class DB{
		const SETUP_FILEPATH = "../settings.json";
		private $db = null;

		/**
		 * Default Constructor
		 * @param string $user     username of DB
		 * @param string $password password of DB
		 * @param string $dbName   name of DB
		 * @param string $host     host of DB
		 * @param string $charset  charset used by DB
		 */
		function __construct($user=null,$password=null,$dbName=null,$host=null,$charset='utf8'){
			if($user == null && $password == null && $dbname == null && $host == null){
				$config=json_decode(file_get_contents(self::SETUP_FILEPATH),true);
				$user = $config['dbUser'];
				$password = $config['dbPsw'];
				$dbname = $config['dbName'];
				$host = $config['dbhost'];
			}
			
			$this->db = new PDO('mysql:
				dbname='.$dbname.';
				host='.$host.';
				charset='.$charset,
				$user,
				$password);
			// Activate parametrized queries
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			// Setting error code in this way allows exception catching
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}

		public function getDB(){
			return $this->db;
		}
	}

 ?>