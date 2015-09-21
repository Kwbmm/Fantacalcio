<?php
  use Symfony\Component\HttpFoundation\Request;

  $app->get('/buy',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect('//'.$app['request']->getHttpHost().'/login');
    $now = time();
    if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
      $closeStart = date('d-m-y H:i',$app['closeTime']);
      $closeEnd = date('d-m-y H:i',$app['openTime']);
      $extra = array('warning' => 'Il mercato è chiuso dal '.$closeStart." al ".$closeEnd);
      $twigParameters = getTwigParameters('Acquista',$app['siteName'],'buy',$app['userMoney'],$extra);
    }
    else
      $twigParameters = getTwigParameters('Acquista',$app['siteName'],'buy',$app['userMoney'],array('closeTime'=>date('d-m-y H:i',$app['closeTime'])));      
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->post('/buy',function (Request $req) use($app){
    $name = $req->get('form-name');
    $roles['POR'] = $req->get('form-por');
    $roles['DIF'] = $req->get('form-dif');
    $roles['CEN'] = $req->get('form-cen');
    $roles['ATT'] = $req->get('form-att');
    $sanitizedRoles = array();
    $i=0;
    foreach ($roles as $key => &$value) {
      $value = sanitizeInput($app['conn'],$value);
      //Check values correctness
      if(isset($value) && !empty($value)){
        if(!in_array($key,$roles,true))
          $app->abort(467,"Si è verificato un errore nella ricerca..",array('page'=>'Acquista'));
        $sanitizedRoles[$i] = $value;
        $i++;
      }
    }
    $price = $req->get('form-price');
    $name = sanitizeInput($app['conn'],$name);
    $name = trim($name);
    $price = (int)sanitizeInput($app['conn'],$price);

    $user = $_SESSION['user'];
    $query = "SELECT UID FROM user WHERE username='$user'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__.' ('.__LINE__.')');
    $row = mysqli_fetch_row($result);
    $uid = $row[0];

    //Get the players that are not in the user_roster of the user
    $query = "  SELECT DISTINCT soccer_player.SPID, Name, Position, Team, Cost
                FROM soccer_player
                WHERE soccer_player.SPID NOT IN( 
                  SELECT SPID FROM user_roster WHERE user_roster.UID = '$uid')
                AND ";
    if($name !== '')
      $query .= "Name LIKE '%$name%'";
    else
      $query .= "Name LIKE '%'";

    if(isset($sanitizedRoles) && !empty($sanitizedRoles))
      for($i=0; $i < count($sanitizedRoles);$i++){
        $role = $sanitizedRoles[$i];
        if($i===0 && $i !== count($sanitizedRoles)-1) 
          //First iteration, add the AND and (
          $query .= " AND (Position = '$role'";
        elseif($i===0 && $i === count($sanitizedRoles)-1)
          //First and last iteration, add the AND and ()
          $query .= " AND (Position = '$role')";
        elseif($i === count($sanitizedRoles)-1)
          //Last iteration, add the OR and )
          $query .= " OR Position = '$role')";
        else
          //Middle iterations add the OR
          $query .= " OR Position = '$role'";
      } //End for
    else //$sanitizedRoles is empty
      $query .= " AND Position LIKE '%'";
    
    if($price !== 0)
      $query .= " AND Cost <= '$price'";
    else
      $query .= " AND Cost LIKE '%'";
    $query .= " ORDER BY Position DESC, Cost ASC, Name ASC";

    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__.' ('.__LINE__.')');
    $players = array();
    $i=0;
    while(($row = mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null){
      $row['Cost'] = $row['Cost'];
      $players[$i] = $row;
      $i++;
    }

    $twigParameters = getTwigParameters('Cerca',$app['siteName'],'buy',$app['userMoney'],array('players'=>$players,'closeTime'=>date('d-m-y H:i',$app['closeTime'])));
    return $app['twig']->render('index.twig',$twigParameters);    
  });
?>