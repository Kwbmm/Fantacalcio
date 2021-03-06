<?php
  use Symfony\Component\HttpFoundation\Request;

  $app->get('/marks',function() use($app){
    /*
      Fetch the last played mid, and redirect to it.
      We want to keep everything together into one single route.
    */
    $now = time();
    $query = "SELECT MAX(MID) FROM match_day WHERE match_day.end <= '$now'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452, __FILE__." (".__LINE__.")");
    $row = mysqli_fetch_row($result);
    $mid = $row[0];
    return $app->redirect('//'.$app['request']->getHttpHost().'/marks/day-'.$mid);
  });

  $app->get('/marks/day-{day}', function($day) use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect('//'.$app['request']->getHttpHost().'/login');
    
    $uid = getUID($app['conn'],$_SESSION['user']);
    $now = time();

    //Select the formation of the user from the requested MID
    $query = "SELECT disposition as role, Name as name
              FROM user_formation, soccer_player
              WHERE user_formation.SPID = soccer_player.SPID 
              AND user_formation.UID = '$uid'
              AND user_formation.MID = '$day'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");

    while (($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) !== null)
      $formation[$row['role']] = array($row['name'],(float)0);
    if(isset($formation)){
      //Select the player marks of the requested MID, for the user
      $query = "SELECT disposition as role, Name as name, mark
                FROM player_mark, user_formation, soccer_player
                WHERE player_mark.SPID = user_formation.SPID
                AND player_mark.SPID = soccer_player.SPID
                AND user_formation.SPID = soccer_player.SPID
                AND user_formation.UID = '$uid'
                AND user_formation.MID = player_mark.MID
                AND player_mark.MID = '$day'";
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");

      while (($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) !== null)
        $marks[$row['role']] = array($row['name'],(float)$row['mark']);
      if(isset($marks)){
        $notPlayed = array_diff_key($formation,$marks);

        //This is done just to enforce the order when the output is shown
        $playerMarks = array( 'POR'     =>'',
                              'DIF-1'   =>'',
                              'DIF-2'   =>'',
                              'DIF-3'   =>'',
                              'DIF-4'   =>'',
                              'DIF-5'   =>'',
                              'CEN-1'   =>'',
                              'CEN-2'   =>'',
                              'CEN-3'   =>'',
                              'CEN-4'   =>'',
                              'CEN-5'   =>'',
                              'ATT-1'   =>'',
                              'ATT-2'   =>'',
                              'ATT-3'   =>'',
                              'POR-R'   =>'',
                              'DIF-R-1' =>'',
                              'DIF-R-2' =>'',
                              'CEN-R-1' =>'',
                              'CEN-R-2' =>'',
                              'ATT-R-1' =>'',
                              'ATT-R-2' =>'');
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
        unset($marks);
        $marks = $playerMarks;
        $total = (float)0;
        if($marks['POR']['mark'] === (float)0 ){
          $total += $marks['POR-R']['mark'];
          unset($marks['POR-R']['mark']);
        }
        else
          $total += $marks['POR']['mark'];
        
        if($marks['DIF-1']['mark'] === (float)0){
          if(isset($marks['DIF-R-1']['mark']) && $marks['DIF-R-1']['mark'] !== (float)0 ){
            $total += $marks['DIF-R-1']['mark'];
            unset($marks['DIF-R-1']['mark']);
          }
          elseif(isset($marks['DIF-R-2']['mark']) && $marks['DIF-R-2']['mark'] !== (float)0){
            $total += $marks['DIF-R-2']['mark'];
            unset($marks['DIF-R-2']['mark']);
          }
        }
        else
          $total += $marks['DIF-1']['mark'];
        if($marks['DIF-2']['mark'] === (float)0){
          if(isset($marks['DIF-R-1']['mark']) && $marks['DIF-R-1']['mark'] !== (float)0 ){
            $total += $marks['DIF-R-1']['mark'];
            unset($marks['DIF-R-1']['mark']);
          }
          elseif(isset($marks['DIF-R-2']['mark']) && $marks['DIF-R-2']['mark'] !== (float)0){
            $total += $marks['DIF-R-2']['mark'];
            unset($marks['DIF-R-2']['mark']);
          }
        }
        else
          $total += $marks['DIF-2']['mark'];
        if($marks['DIF-3']['mark'] === (float)0){
          if(isset($marks['DIF-R-1']['mark']) && $marks['DIF-R-1']['mark'] !== (float)0 ){
            $total += $marks['DIF-R-1']['mark'];
            unset($marks['DIF-R-1']['mark']);
          }
          elseif(isset($marks['DIF-R-2']['mark']) && $marks['DIF-R-2']['mark'] !== (float)0){
            $total += $marks['DIF-R-2']['mark'];
            unset($marks['DIF-R-2']['mark']);
          }
        }
        else
          $total += $marks['DIF-3']['mark'];
        if(isset($marks['DIF-4']['mark'])){
          if($marks['DIF-4']['mark'] === (float)0){
            if(isset($marks['DIF-R-1']['mark']) && $marks['DIF-R-1']['mark'] !== (float)0 ){
              $total += $marks['DIF-R-1']['mark'];
              unset($marks['DIF-R-1']['mark']);
            }
            elseif(isset($marks['DIF-R-2']['mark']) && $marks['DIF-R-2']['mark'] !== (float)0){
              $total += $marks['DIF-R-2']['mark'];
              unset($marks['DIF-R-2']['mark']);
            }
          }
          else
            $total += $marks['DIF-4']['mark'];
        }
        if(isset($marks['DIF-5']['mark'])){
          if($marks['DIF-5']['mark'] === (float)0){
            if(isset($marks['DIF-R-1']['mark']) && $marks['DIF-R-1']['mark'] !== (float)0 ){
              $total += $marks['DIF-R-1']['mark'];
              unset($marks['DIF-R-1']['mark']);
            }
            elseif(isset($marks['DIF-R-2']['mark']) && $marks['DIF-R-2']['mark'] !== (float)0){
              $total += $marks['DIF-R-2']['mark'];
              unset($marks['DIF-R-2']['mark']);
            }
          }
          else
            $total += $marks['DIF-5']['mark'];
        }
        if($marks['CEN-1']['mark'] === (float)0){
          if(isset($marks['CEN-R-1']['mark']) && $marks['CEN-R-1']['mark'] !== (float)0 ){
            $total += $marks['CEN-R-1']['mark'];
            unset($marks['CEN-R-1']['mark']);
          }
          elseif(isset($marks['CEN-R-2']['mark']) && $marks['CEN-R-2']['mark'] !== (float)0){
            $total += $marks['CEN-R-2']['mark'];
            unset($marks['CEN-R-2']['mark']);
          }
        }
        else
          $total += $marks['CEN-1']['mark'];
        if($marks['CEN-2']['mark'] === (float)0){
          if(isset($marks['CEN-R-1']['mark']) && $marks['CEN-R-1']['mark'] !== (float)0 ){
            $total += $marks['CEN-R-1']['mark'];
            unset($marks['CEN-R-1']['mark']);
          }
          elseif(isset($marks['CEN-R-2']['mark']) && $marks['CEN-R-2']['mark'] !== (float)0){
            $total += $marks['CEN-R-2']['mark'];
            unset($marks['CEN-R-2']['mark']);
          }
        }
        else
          $total += $marks['CEN-2']['mark'];
        if($marks['CEN-3']['mark'] === (float)0){
          if(isset($marks['CEN-R-1']['mark']) && $marks['CEN-R-1']['mark'] !== (float)0 ){
            $total += $marks['CEN-R-1']['mark'];
            unset($marks['CEN-R-1']['mark']);
          }
          elseif(isset($marks['CEN-R-2']['mark']) && $marks['CEN-R-2']['mark'] !== (float)0){
            $total += $marks['CEN-R-2']['mark'];
            unset($marks['CEN-R-2']['mark']);
          }
        }
        else
          $total += $marks['CEN-3']['mark'];
        if(isset($marks['CEN-4']['mark'])){
          if($marks['CEN-4']['mark'] === (float)0){
            if(isset($marks['CEN-R-1']['mark']) && $marks['CEN-R-1']['mark'] !== (float)0 ){
              $total += $marks['CEN-R-1']['mark'];
              unset($marks['CEN-R-1']['mark']);
            }
            elseif(isset($marks['CEN-R-2']['mark']) && $marks['CEN-R-2']['mark'] !== (float)0){
              $total += $marks['CEN-R-2']['mark'];
              unset($marks['CEN-R-2']['mark']);
            }
          }
          else
            $total += $marks['CEN-4']['mark'];
        }
        if(isset($marks['CEN-5']['mark'])){
          if($marks['CEN-5']['mark'] === (float)0){
            if(isset($marks['CEN-R-1']['mark']) && $marks['CEN-R-1']['mark'] !== (float)0 ){
              $total += $marks['CEN-R-1']['mark'];
              unset($marks['CEN-R-1']['mark']);
            }
            elseif(isset($marks['CEN-R-2']['mark']) && $marks['CEN-R-2']['mark'] !== (float)0){
              $total += $marks['CEN-R-2']['mark'];
              unset($marks['CEN-R-2']['mark']);
            }
          }
          else
            $total += $marks['CEN-5']['mark'];
        }
        if($marks['ATT-1']['mark'] === (float)0){
          if(isset($marks['ATT-R-1']['mark']) && $marks['ATT-R-1']['mark'] !== (float)0 ){
            $total += $marks['ATT-R-1']['mark'];
            unset($marks['ATT-R-1']['mark']);
          }
          elseif(isset($marks['ATT-R-2']['mark']) && $marks['ATT-R-2']['mark'] !== (float)0){
            $total += $marks['ATT-R-2']['mark'];
            unset($marks['ATT-R-2']['mark']);
          }
        }
        else
          $total += $marks['ATT-1']['mark'];
        if(isset($marks['ATT-2']['mark'])){
          if($marks['ATT-2']['mark'] === (float)0){
            if(isset($marks['ATT-R-1']['mark']) && $marks['ATT-R-1']['mark'] !== (float)0 ){
              $total += $marks['ATT-R-1']['mark'];
              unset($marks['ATT-R-1']['mark']);
            }
            elseif(isset($marks['ATT-R-2']['mark']) && $marks['ATT-R-2']['mark'] !== (float)0){
              $total += $marks['ATT-R-2']['mark'];
              unset($marks['ATT-R-2']['mark']);
            }
          }
          else
            $total += $marks['ATT-2']['mark'];
        }
        if(isset($marks['ATT-3']['mark'])){
          if($marks['ATT-3']['mark'] === (float)0){
            if(isset($marks['ATT-R-1']['mark']) && $marks['ATT-R-1']['mark'] !== (float)0 ){
              $total += $marks['ATT-R-1']['mark'];
              unset($marks['ATT-R-1']['mark']);
            }
            elseif(isset($marks['ATT-R-2']['mark']) && $marks['ATT-R-2']['mark'] !== (float)0){
              $total += $marks['ATT-R-2']['mark'];
              unset($marks['ATT-R-2']['mark']);
            }
          }
          else
            $total += $marks['ATT-3']['mark'];
        }
      }//EOF if isset($marks)
    } //End if isset($formation)

    //Get how many MIDs we played
    $query = "SELECT MAX(MID) FROM match_day WHERE match_day.end <= '$now'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    
    $playedDays = mysqli_fetch_row($result);
    $playedDays = $playedDays[0];
    
    if(!isset($formation)) //If true, user has no formation
      $twigParameters = getTwigParameters('Voti Giornata '.$day,$app['siteName'],'marks',$app['userMoney'],array('warning'=>'Non hai schierato la formazione, non ci sono voti da mostrare..','currentMid' =>$day,'playedDays' => $playedDays));
    elseif(!isset($marks)) //If true, user has no marks
      $twigParameters = getTwigParameters('Voti Giornata '.$day,$app['siteName'],'marks',$app['userMoney'],array('currentMid' =>$day,'playedDays' => $playedDays,'playerMarks'=>array('POR' => ''),'total'=>0.0));
    else //User has formation and marks
      $twigParameters = getTwigParameters('Voti Giornata '.$day,$app['siteName'],'marks',$app['userMoney'],array('currentMid' =>$day,'playedDays' => $playedDays,'playerMarks'=>$playerMarks,'total'=>$total));
    return $app['twig']->render('index.twig',$twigParameters);
  })->assert('day','[1-9]|[1-2][0-9]|3[0-8]');
?>