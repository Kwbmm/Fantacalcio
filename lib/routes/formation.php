<?php
	use Symfony\Component\HttpFoundation\Request;
	require_once __DIR__."/../includes/usersClass.php";
	require_once __DIR__."/../includes/dbClass.php";
	require_once __DIR__."/../includes/utilClass.php";

	$app->get('/formation',function () use($app){
		  	if(!isset($_SESSION['user']))
		  		return $app->redirect('//'.$app['request']->getHttpHost().'/login');
		  	$now = time();
		  	$closeStart = date_create_from_format("U",$app['closeTime']);
		  	$closeStart->setTimezone(new DateTimeZone("Europe/Rome"));
			if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
				$closeEnd = date_create_from_format("U",$app['openTime']);
				$closeEnd->setTimezone(new DateTimeZone("Europe/Rome"));
			}
			$users = new Users;
			$user = $users->getUserByUsername($_SESSION['user']);
			$roster = $user->getRoster();
			if(count($roster->getPlayers()) !== 23){
				$twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('error' => 'Sembra che la tua rosa sia incompleta'));
			}	
			else{
				//Show the soccer players that play
				//TODO remove UID and user $user->getUID();
				$uid = getUID($app['conn'],$_SESSION['user']);
				$playingPlayers = array('POR'=>'',
					'DIF-1'=>'',
					'DIF-2'=>'',
					'DIF-3'=>'',
					'DIF-4'=>'',
					'DIF-5'=>'',
					'CEN-1'=>'',
					'CEN-2'=>'',
					'CEN-3'=>'',
					'CEN-4'=>'',
					'CEN-5'=>'',
					'ATT-1'=>'',
					'ATT-2'=>'',
					'ATT-3'=>'',
					'POR-R'=>'',
					'DIF-R-1'=>'',
					'DIF-R-2'=>'',
					'CEN-R-1'=>'',
					'CEN-R-2'=>'',
					'ATT-R-1'=>'',
					'ATT-R-2'=>'');
				$query = "SELECT soccer_player.Name as name, disposition as role
				FROM soccer_player, user_formation,match_day
				WHERE user_formation.UID = '$uid'
				AND soccer_player.SPID = user_formation.SPID
				AND user_formation.MID = match_day.MID
				AND user_formation.MID = (
				SELECT MIN(MID) FROM match_day
				WHERE '$now' <= match_day.end)";
				$result = getResult($app['conn'],$query);
				if($result === false)
					$app->abort(452,__FILE__." (".__LINE__.")");
				$filled = false;
				while (($row=mysqli_fetch_array($result,MYSQLI_ASSOC))!== null) {
					switch ($row['role']) {
						case 'POR':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-1':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-2':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-3':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-4':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-5':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-1':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-2':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-3':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-4':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-5':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'ATT-1':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'ATT-2':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'ATT-3':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'POR-R':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-R-1':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'DIF-R-2':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-R-1':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'CEN-R-2':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'ATT-R-1':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						case 'ATT-R-2':
						$playingPlayers[$row['role']] = $row['name'];
						$filled = true;
						break;
						default:
					# Should never happen
						break;
					}
				}
				if($filled){
				if(isset($closeStart) && isset($closeEnd)) //If market is closed
				$twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('playingPlayers'=>$playingPlayers,'warning' => 'Questa pagina è bloccata dal '.$closeStart->format('d-m-y H:i')." al ".$closeEnd->format('d-m-y H:i')));        
				else
					$twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('playingPlayers'=>$playingPlayers,'closeTime'=>$closeStart->format('d-m-y H:i')));
			}
			else{
				if(isset($closeStart) && isset($closeEnd)) //If market is closed
				$twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('warning' => 'Questa pagina è bloccata dal '.$closeStart->format('d-m-y H:i')." al ".$closeEnd->format('d-m-y H:i')));
				else
					$twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('closeTime'=>$closeStart->format('d-m-y H:i')));
			}
		}
		return $app['twig']->render('index.twig',$twigParameters);
	});

$app->post('/formation',function(Request $req) use($app){
	$modulo = sanitizeInput($app['conn'],$req->get('mod'));
	$modulo = explode("-", $modulo);
	$formation['DIF']['mod'] = $modulo[0];
	$formation['CEN']['mod'] = $modulo[1];
	$formation['ATT']['mod'] = $modulo[2];

	$uid = getUID($app['conn'],$_SESSION['user']);
	//Fetch the players that can be put in the formation
	$query = "SELECT soccer_player.SPID as spid, soccer_player.Name as name, Position as pos
	FROM soccer_player, user_roster
	WHERE user_roster.UID = '$uid'
	AND user_roster.SPID = soccer_player.SPID
	ORDER BY Position DESC, Cost DESC, Name ASC";
	$result = getResult($app['conn'],$query);
	if($result === false)
		$app->abort(452,__FILE__." (".__LINE__.")");
	while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null){
		switch ($row['pos']) {
			case 'POR':
			$formation['POR']['players'][$row['spid']] = $row['name'];
			break;
			case 'DIF':
			$formation['DIF']['players'][$row['spid']] = $row['name'];
			break;
			case 'CEN':
			$formation['CEN']['players'][$row['spid']] = $row['name'];
			break;
			case 'ATT':
			$formation['ATT']['players'][$row['spid']] = $row['name'];
			break;        
			default:
		  # Should never happen
			break;
		}
	}
	$twigParameters = getTwigParameters('Formazione',$app['siteName'],'formation',$app['userMoney'],array('formation'=>$formation));
	return $app['twig']->render('index.twig',$twigParameters);
});

$app->post('/confirmForm',function(Request $req) use($app){
	$reqItems = $req->request->all();
	$sanitizedArray = array();
	$modulo = array_fill(0,3,null);
	foreach ($reqItems as $role => &$SPID) {
	  //Check the roles
		$role = sanitizeInput($app['conn'],$role);
		if(preg_match('/(POR|DIF|CEN|ATT)(\-)?(R)?(\-)?(\d)?/', $role,$match) !== 1)
			$app->abort(475,"Si è verificato un errore..");
		switch ($match[1]) {
			case 'DIF':
			$modulo[0]++;
			break;
			case 'CEN':
			$modulo[1]++;
			break;
			case 'ATT':
			$modulo[2]++;
			break;
			default:
		  # Don't care..
			break;
		}
	  //Check the SPID
		$SPID = sanitizeInput($app['conn'],$SPID);
		if(preg_match('/\d+/',$SPID) !== 1)
			$app->abort(476,"Si è verificato un errore..");
		$sanitizedArray[$role] = $SPID;
	}
	$modulo[0] -= 2;//DIF
	$modulo[1] -= 2;//CEN
	$modulo[2] -= 2;//ATT

	if(($rv = begin($app['conn'])) !== true)
		$app->abort($rv->getCode(),$rv->getMessage());

	$uid = getUID($app['conn'],$_SESSION['user']);
	//Check if the formation for this match day is already made, if so reset!
	$now = time();
	$query = "SELECT * FROM user_formation, match_day
	WHERE user_formation.MID = match_day.MID
	AND user_formation.MID = (
	SELECT MIN(MID) FROM match_day
	WHERE match_day.start > '$now')
	AND UID = '$uid'";
	$result = getResult($app['conn'],$query);
	if(mysqli_affected_rows($app['conn']) !== 0){ //Formation is already done
		$query = "SELECT * FROM user_formation
		WHERE UID = '$uid'
		AND user_formation.MID = (
		SELECT MIN(MID) FROM match_day
		WHERE match_day.start > '$now')
		FOR UPDATE";
		$result = getResult($app['conn'],$query);
		if($result === false){
			rollback($app['conn']);
			$app->abort(452,__FILE__." (".__LINE__.")");
		}
	  //Delete the formation to make a new one
		$query = "DELETE FROM user_formation
		WHERE user_formation.MID = (
		SELECT MIN(MID) FROM match_day
		WHERE match_day.start > '$now')
		AND UID = '$uid'";
		$result = getResult($app['conn'],$query);
		if($result === false){
			rollback($app['conn']);
			$app->abort(452,__FILE__." (".__LINE__.")");
		}
	}
	//Set the players as playing
	foreach ($sanitizedArray as $role => $SPID) {
		$pk = getLastPrimaryKey($app['conn'],'user_formation')+1;
	  //Fetch the matchday
		$query = "SELECT MIN(MID) FROM match_day
		WHERE match_day.start > '$now'";
		$result = getResult($app['conn'],$query);
		if($result === false){
			rollback($app['conn']);
			$app->abort(452,__FILE__." (".__LINE__.")");
		}
		$row = mysqli_fetch_row($result);
		$MID = (int)$row[0];
		$query = "INSERT INTO user_formation VALUES('$pk','$uid','$SPID','$MID','$role')";
		$result = getResult($app['conn'],$query);
		if($result === false){
			rollback($app['conn']);
			$app->abort(452,__FILE__." (".__LINE__.")");
		}
	}
	commit($app['conn']);

	//Show the player that play
	$playingPlayers = array('POR'=>'',
		'DIF-1'=>'',
		'DIF-2'=>'',
		'DIF-3'=>'',
		'DIF-4'=>'',
		'DIF-5'=>'',
		'CEN-1'=>'',
		'CEN-2'=>'',
		'CEN-3'=>'',
		'CEN-4'=>'',
		'CEN-5'=>'',
		'ATT-1'=>'',
		'ATT-2'=>'',
		'ATT-3'=>'',
		'POR-R'=>'',
		'DIF-R-1'=>'',
		'DIF-R-2'=>'',
		'CEN-R-1'=>'',
		'CEN-R-2'=>'',
		'ATT-R-1'=>'',
		'ATT-R-2'=>'');
	$query = "SELECT soccer_player.Name as name, disposition as role
	FROM soccer_player, user_formation
	WHERE user_formation.UID = '$uid'
	AND soccer_player.SPID = user_formation.SPID
	AND user_formation.MID = (
	SELECT MIN(MID) FROM match_day
	WHERE match_day.start > '$now')";
	$result = getResult($app['conn'],$query);
	if($result === false)
		$app->abort(452,__FILE__." (".__LINE__.")");
	while (($row=mysqli_fetch_array($result,MYSQLI_ASSOC))!== null) {
		switch ($row['role']) {
			case 'POR':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-1':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-2':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-3':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-4':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-5':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-1':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-2':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-3':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-4':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-5':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'ATT-1':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'ATT-2':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'ATT-3':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'POR-R':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-R-1':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'DIF-R-2':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-R-1':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'CEN-R-2':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'ATT-R-1':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			case 'ATT-R-2':
			$playingPlayers[$row['role']] = $row['name'];
			break;
			default:
		  # Should never happen
			break;
		}
	}

	$twigParameters = getTwigParameters('Formazione',$app['siteName'],'confirmForm',$app['userMoney'],array('success'=>'La tua formazione è stata schierata!','playingPlayers'=>$playingPlayers));
	return $app['twig']->render('index.twig',$twigParameters);
});
?>