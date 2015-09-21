<?php
  use Symfony\Component\HttpFoundation\Request;

  $app->get('/roster',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect('//'.$app['request']->getHttpHost().'/login');

    //Get UID
    $user = $_SESSION['user'];
    $query = "SELECT UID FROM user WHERE username='$user'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $row = mysqli_fetch_row($result);
    $uid = $row[0];   

    //Get the soccer_players of the user
    $query = "  SELECT Position as pos, Name as name, Cost as cost, Team as team, user_roster.SPID as SPID
                FROM user_roster, soccer_player
                WHERE user_roster.UID = '$uid'
                AND user_roster.SPID = soccer_player.SPID
                ORDER BY Position DESC, Cost ASC, Name ASC";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $userPlayers = array();
    $i = 0;
    while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null){
      $userPlayers[$i] = $row;
      $i++;
    }

    $rosterStatus = array('POR'=>0,'DIF'=>0,'CEN'=>0,'ATT'=>0);
    foreach ($userPlayers as $player) {
      switch ($player['pos']) {
        case 'POR':
          $rosterStatus['POR']++;
          break;
        case 'DIF':
          $rosterStatus['DIF']++;
          break;
        case 'CEN':
          $rosterStatus['CEN']++;
          break;
        case 'ATT':
          $rosterStatus['ATT']++;
          break;
        default:
          # Should never happen..
          break;
      }
    }
    $now = time();
    if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
      $closeStart = date('d-m-y H:i',$app['closeTime']);
      $closeEnd = date('d-m-y H:i',$app['openTime']);
      $twigParameters = getTwigParameters('Rosa',$app['siteName'],'roster',$app['userMoney'],array('userPlayers'=>$userPlayers,'rosterStatus' => $rosterStatus,'warning' => 'Il mercato è chiuso dal '.$closeStart." al ".$closeEnd));      
    }
    else
      $twigParameters = getTwigParameters('Rosa',$app['siteName'],'roster',$app['userMoney'],array('userPlayers' => $userPlayers,'rosterStatus' => $rosterStatus));
    return $app['twig']->render('index.twig',$twigParameters);
  });
  
  $app->post('/roster',function (Request $req) use($app){
    $reqItems = $req->request->all();
    $sanitizedValues = array();
    foreach ($reqItems as $spid => $price)
      $sanitizedValues[sanitizeInput($app['conn'],$spid)] = sanitizeInput($app['conn'],$price);

    if(empty($sanitizedValues))
      $app->abort(472,"Sembra che non ci sia nulla da fare..");
    $user = $_SESSION['user'];
    $query = "SELECT UID FROM user WHERE username='$user'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $row = mysqli_fetch_row($result);
    $uid = $row[0];

    $totalPrice = 0;

    if(($rv = begin($app['conn'])) !== true)
      $app->abort($rv->getCode(),$rv->getMessage());

    foreach ($sanitizedValues as $spid => $price) {
      $totalPrice += $price;
      $query = "DELETE FROM user_roster
                WHERE UID = '$uid' 
                AND SPID = '$spid'";
      $result = getResult($app['conn'],$query);
      if($result === false){
        rollback($app['conn']);
        $app->abort(452,__FILE__." (".__LINE__.")");
      }
      if(mysqli_affected_rows($app['conn']) !== 1 ){ //The deletion failed
        rollback($app['conn']);
        $app->abort(470,"Si è verificato un errore durante l'operazione DELETE");        
      }
      $now = time();
      //Delete the formation of the user of the coming MID in user_formation
      $query = "DELETE FROM user_formation
                WHERE UID = '$uid'
                AND MID = (
                  SELECT MIN(MID) FROM match_day
                  WHERE '$now' < start)";
      $result = getResult($app['conn'],$query);
      if($result === false){
        rollback($app['conn']);
        $app->abort(452,__FILE__." (".__LINE__.")");
      }

      $query = "UPDATE user, soccer_player
                SET user.money = soccer_player.Cost+user.money
                WHERE user.UID = '$uid'
                AND soccer_player.SPID = '$spid'";
      $result = getResult($app['conn'],$query);
      if($result === false){
        rollback($app['conn']);
        $app->abort(452,__FILE__." (".__LINE__.")");
      }
      if(mysqli_affected_rows($app['conn']) !== 1 ){ //The deletion failed
        rollback($app['conn']);
        $app->abort(471,"Si è verificato un errore durante l'operazione UPDATE");        
      }
    }
    commit($app['conn']);

    //Refetch the userMoney
    $query = "SELECT money FROM user WHERE UID = '$uid'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $row = mysqli_fetch_row($result);
    $app['userMoney'] = $row[0];

    //Get the soccer_players of the user
    $query = "  SELECT Position as pos, Name as name, Cost as cost, Team as team, user_roster.SPID as SPID
                FROM user_roster, soccer_player
                WHERE user_roster.UID = '$uid'
                AND user_roster.SPID = soccer_player.SPID
                ORDER BY Position DESC, Cost ASC, Name ASC";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $userPlayers = array();
    $i = 0;
    while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null){
      $userPlayers[$i] = $row;
      $i++;
    }
    
    $rosterStatus = array('POR'=>0,'DIF'=>0,'CEN'=>0,'ATT'=>0);
    foreach ($userPlayers as $player) {
      switch ($player['pos']) {
        case 'POR':
          $rosterStatus['POR']++;
          break;
        case 'DIF':
          $rosterStatus['DIF']++;
          break;
        case 'CEN':
          $rosterStatus['CEN']++;
          break;
        case 'ATT':
          $rosterStatus['ATT']++;
          break;
        default:
          # Should never happen..
          break;
      }
    }
    $twigParameters = getTwigParameters('Rosa',$app['siteName'],'roster',$app['userMoney'],array('success'=>'I giocatori scelti sono stati ceduti!','userPlayers' => $userPlayers,'rosterStatus'=>$rosterStatus));
    return $app['twig']->render('index.twig',$twigParameters);
  });
?>