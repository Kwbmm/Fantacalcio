<?php 
  $app->get('/home',function() use($app){
    $now = time();
    $query =  "SELECT username, points FROM user, scores
              WHERE user.UID = scores.UID
              AND scores.MID = (
                SELECT MAX(MID) FROM match_day
                WHERE match_day.end <= '$now')
              ORDER BY points DESC";

    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $sequence = array();
    while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC))!== null)
      array_push($sequence, $row);
    $twigParameters = getTwigParameters('Home',$app['siteName'],'home',$app['userMoney'],array('sequence'=>$sequence));
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->get('/',function() use($app){
    return $app->redirect($_SERVER['REQUEST_URI'].'home');
  });

?>