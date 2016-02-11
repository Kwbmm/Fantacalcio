<?php
	/**
	* 
	*/
	class Formation	{		
		private $mid = null;
		private $players = array();
		function __construct($mid,$players) {
			foreach ($players as $pIndex => $values) {
				$players[$values['disposition']] = $values[''];
			}
			echo $mid."<br />";
			var_dump($players);
			echo "<br /><br />";
		}
	}
?>