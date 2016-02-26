<?php 
	/**
	 * Class managing initialization of DB.
	 * Class uses PDO.
	 * 
	 * @author Marco Ardizzone
	 * @link twitter.com/marcoardiz
	 */
	class DB{
		private static $SETUP_FILEPATH = null;

		private static $user=null,$password=null,$dbname=null,$host=null,$charset='utf8';
		private static $db = null;
		private static $instance = null;

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
		public static function getInstance($user=null,$password=null,$dbname=null,$host=null,$charset='utf8'){
			self::$SETUP_FILEPATH = __DIR__."/../settings.json";
			if(!isset($user) && !isset($password) && !isset($dbname) && !isset($host)){
				$config=json_decode(file_get_contents(self::$SETUP_FILEPATH),true);
				if($config['useLocal']){
					self::$user = $config['dbLocalUser'];
					self::$password = $config['dbLocalPsw'];
					self::$dbname = $config['dbLocalName'];
					self::$host = $config['dbLocalHost'];					
				}
				else{
					self::$user = $config['dbUser'];
					self::$password = $config['dbPsw'];
					self::$dbname = $config['dbName'];
					self::$host = $config['dbHost'];					
				}
			}
			else{
				self::$user = $user;
				self::$password = $password;
				self::$dbname = $dbname;
				self::$host = $host;				
			}
			self::$instance = new DB;
			return self::$instance->db;

		}

		private function __construct(){
			$this->db = new PDO(
				'mysql:'.
				'host='.self::$host.';'.
				'dbname='.self::$dbname.';'.
				'charset='.self::$charset,
				self::$user,
				self::$password);
			// Activate parametrized queries
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			// Setting error code in this way allows exception catching
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	}

 ?>