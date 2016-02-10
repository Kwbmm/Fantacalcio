<?php 
  require_once __DIR__."/../includes/userClass.php";
  require_once __DIR__."/../includes/dbClass.php";
  $app->get('/home',function() use($app){
    $now = time();
    $i=0;
    $user = new User(1,new DB('root','','fantacalcio','localhost'));
    myDump($user->getFormations());
    do{
      $query =  "SELECT scores.MID as MID, username, points FROM user, scores
                WHERE user.UID = scores.UID
                AND scores.MID = (
                  SELECT MAX(MID)-'$i' FROM match_day
                  WHERE match_day.end <= '$now')
                ORDER BY points DESC";

      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");
      $sequence = array();
      while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC))!== null){
        $mid = $row['MID']; //Save the match ID
        unset($row['MID']); //Remove it from the row
        array_push($sequence, $row); //Put the row in the result to display
      }
      $i++;
    }while(empty($sequence) && !isset($mid));
    $twigParameters = getTwigParameters('Home',$app['siteName'],'home',$app['userMoney'],array('sequence'=>$sequence));
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->get('/',function() use($app){
    return $app->redirect('//'.$app['request']->getHttpHost().'/home');
  });

?>