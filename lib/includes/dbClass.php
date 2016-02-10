<?php 
	/**
	 * Class managing initialization of DB.
	 * Class uses PDO.
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	 */
	class DB{
		const SETUP_FILEPATH = null;
		private $db = null;

		/**
		 * Default Constructor: if no parameter is passed, credentials are read from
		 * settings.json file, otherwise the passed parameters are used to connect
		 * to the DB.
		 * 
		 * @param string $user     username of DB
		 * @param string $password password of DB
		 * @param string $dbName   name of DB
		 * @param string $host     host of DB
		 * @param string $charset  charset used by DB
		 */
		function __construct($user=null,$password=null,$dbname=null,$host=null,$charset='utf8'){
			$this->SETUP_FILEPATH = __DIR__."/../settings.json";
			if($user == null && $password == null && $dbname == null && $host == null){
				$config=json_decode(file_get_contents($this->SETUP_FILEPATH),true);
				$user = $config['dbUser'];
				$password = $config['dbPsw'];
				$dbname = $config['dbName'];
				$host = $config['dbHost'];
			}
			$this->db = new PDO(
				'mysql:'.
				'host='.$host.';'.
				'dbname='.$dbname.';'.
				'charset='.$charset,
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