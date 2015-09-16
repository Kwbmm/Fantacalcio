<?php
  use Symfony\Component\HttpFoundation\Request;

  $app->get('/marks',function() use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');

    $uid = getUID($app['conn'],$_SESSION['user']);
    $now = time();

    //Select the formation of the user from the last MID
    $query = "SELECT disposition as role, Name as name
              FROM user_formation, soccer_player
              WHERE user_formation.SPID = soccer_player.SPID 
              AND user_formation.UID = '$uid'
              AND user_formation.MID = (
                SELECT MAX(MID)
                FROM match_day
                WHERE match_day.end <= '$now')";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,__FILE__." (".__LINE__.")");
    }
    while (($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) !== null)
      $formation[$row['role']] = array($row['name'],0);
    
    //Select the player marks of the last played MID, for the user
    $query = "SELECT disposition as role, Name as name, mark
              FROM player_mark, user_formation, soccer_player
              WHERE player_mark.SPID = user_formation.SPID
              AND player_mark.SPID = soccer_player.SPID
              AND user_formation.SPID = soccer_player.SPID
              AND user_formation.UID = '$uid'
              AND player_mark.MID = (
                SELECT MAX(MID)
                FROM match_day
                WHERE match_day.end <= '$now')";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,__FILE__." (".__LINE__.")");
    }
    while (($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) !== null)
      $marks[$row['role']] = array($row['name'],(float)$row['mark']);

    $notPlayed = array_diff_key($formation,$marks);

    //This is done just to enforce the order when the output is shown
    $playerMarks = array( 'POR'=>'',
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
    foreach($marks as $role => $name_mark){
      switch ($role) {
        case 'POR':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-3':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-4':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-5':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-3':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-4':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-5':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-3':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'POR-R':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-R-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-R-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-R-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-R-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-R-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-R-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        default: //Should never happen
          break;
      }
    }

    foreach($notPlayed as $role => $name_mark){
      switch ($role) {
        case 'POR':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-3':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-4':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-5':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-3':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-4':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-5':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-3':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'POR-R':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-R-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'DIF-R-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-R-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'CEN-R-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-R-1':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        case 'ATT-R-2':
          $playerMarks[$role] = array('name'=>$name_mark[0],'mark'=>$name_mark[1]);
          break;
        default: //Should never happen
          break;
      }
    }
    $query = "SELECT MAX(MID) FROM match_day WHERE match_day.end <= '$now'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $mid = mysqli_fetch_row($result);
    $mid = $mid[0];

    /*
      Check if the player has any formation for the last passed MID.
      This is done to understand if the marks are not out but the user made his
      formation (and so we tell him marks are not out yet), or if the user
      didn't have any formation (and so we tell him there will be no marks to show).
    */
    $query = "SELECT * FROM user_formation WHERE MID = '$mid' AND UID = '$uid'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")"); 
    if(mysqli_affected_rows($app['conn']) === 0 ) //If 0 user has no formation
      $twigParameters = getTwigParameters('Voti',$app['siteName'],'marks',$app['userMoney'],array('warning'=>'Non hai schierato la formazione, non ci sono voti da mostrare..','matchDay'=>$mid));
    else
      $twigParameters = getTwigParameters('Voti',$app['siteName'],'marks',$app['userMoney'],array('playerMarks'=>$playerMarks,'matchDay'=>$mid));
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->get('/marks/{day}', function($day) use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');

    $twigParameters = getTwigParameters('Voti Giornata '.$day,$app['siteName'],'marks',$app['userMoney']);
    return $app['twig']->render('index.twig',$twigParameters);
  });
?>