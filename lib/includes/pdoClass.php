<?php 
	
	class DB{
		private $conn = null;
		function __construct($user,$password,$dbName,$host,$charset='utf8'){
			$this->conn = new PDO('mysql:
				dbname='.$dbname.';
				host='.$host.';
				charset='.$charset,
				$user,
				$password);
			// Activate parametrized queries
			$this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			// Setting error code in this way allows exception catching
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	}

 ?>