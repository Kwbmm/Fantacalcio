<?php
  use Symfony\Component\HttpFoundation\Request;

  $app->get('/checkout',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect('//'.$app['request']->getHttpHost().'/login');
    $now = time();
    if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
      $closeStart = date('d-m-y H:i',$app['closeTime']);
      $closeEnd = date('d-m-y H:i',$app['openTime']);
      $extra = array('warning' => 'Il mercato è chiuso dal '.$closeStart." al ".$closeEnd);
      $twigParameters = getTwigParameters('Carrello',$app['siteName'],'checkout',$app['userMoney'],$extra);      
    }
    else{
      $purchases = array();
      $i=0;
      foreach ($_COOKIE as $key => $value) {
        if(preg_match("/\d+/", $key) === 1){
          $player=json_decode($value,true);
          $player['id'] = $key;
          $purchases[$i] = $player;
          $i++;
        }
      }
      //Order by Role, Price
      customSort($purchases);
      $twigParameters = getTwigParameters('Carrello',$app['siteName'],'checkout',$app['userMoney'],array('purchases' => $purchases,'closeTime'=>date('d-m-y H:i',$app['closeTime'])));      
    }
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->post('/checkout',function (Request $req) use($app){
    $reqItems = $req->request->all();
    $totalPrice = 0;
    if(($rv = begin($app['conn'])) !== true)
      $app->abort($rv->getCode(),$rv->getMessage());

    $rolesAmount = array('POR'=>0,'DIF'=>0,'CEN'=>0,'ATT'=>0);
    $sanitizedValues = array();
    foreach ($reqItems as $key => $value) //Value is empty, use key
      array_push($sanitizedValues,sanitizeInput($app['conn'],$key));

    foreach ($sanitizedValues as $key) {      
      //Get the Cost and position of each player. Compute the total price and
      //the total number of players per role. ($rolesAmount)
      $query = "SELECT Position, Cost FROM soccer_player WHERE SPID='$key'";
      $result = getResult($app['conn'],$query);
      if($result === false){
        rollback($app['conn']);
        $app->abort(452,__FILE__." (".__LINE__.")");
      }
      $row = mysqli_fetch_row($result);
      switch ($row[0]){
        case 'POR':
          $rolesAmount['POR']++;
          break;
        case 'DIF':
          $rolesAmount['DIF']++;
          break;
        case 'CEN':
          $rolesAmount['CEN']++;
          break;
        case 'ATT':
          $rolesAmount['ATT']++;
          break;        
        default:
          # Should never happen
          break;
      }
      $totalPrice += $row[1];
    }

    //Get UID and Money, then check if you have enough money to pay
    $user = $_SESSION['user'];
    $query = "SELECT UID, money FROM user WHERE username='$user'";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,__FILE__." (".__LINE__.")");
    }
    $row = mysqli_fetch_row($result);
    $uid = $row[0];
    $userMoney = (int)$row[1];
    if($totalPrice > $userMoney){
      rollback($app['conn']);
      $app->abort(465,"Sembra che tu non abbia abbastanza soldi..");
    }

    //For each role, compute how many players in that role we already have in our roster
    foreach ($rolesAmount as $role => $amount) {
      $query =  "SELECT * FROM user_roster, soccer_player
                  WHERE user_roster.UID = '$uid' 
                  AND soccer_player.Position = '$role'
                  AND user_roster.SPID = soccer_player.SPID";
      $result = getResult($app['conn'],$query);
      if($result === false){
        rollback($app['conn']);
        $app->abort(452,__FILE__." (".__LINE__.")");
      }
      $sqlAmount = mysqli_num_rows($result);
      $sqlAmount += $amount;
      switch ($role) {
        case 'POR':
          if($sqlAmount > 3){
            rollback($app['conn']);
            $app->abort(466,"Hai troppi giocatori nella posizione ".$role);
          }
          break;
        case 'DIF':
          if($sqlAmount > 7){
            rollback($app['conn']);
            $app->abort(466,"Hai troppi giocatori nella posizione ".$role);
          }
          break;
        case 'CEN':
          if($sqlAmount > 8){
            rollback($app['conn']);
            $app->abort(466,"Hai troppi giocatori nella posizione ".$role);
          }
          break;
        case 'ATT':
          if($sqlAmount > 5){
            rollback($app['conn']);
            $app->abort(466,"Hai troppi giocatori nella posizione ".$role);
          }
          break;        
        default:
          # Should never happen
          break;
      }
    }

    foreach ($sanitizedValues as $key) {
      $pk = getLastPrimaryKey($app['conn'],'user_roster')+1;
      $query = "INSERT INTO user_roster VALUES('$pk','$uid','$key')";
      $result = getResult($app['conn'],$query);
      if($result === false){
        rollback($app['conn']);
        $app->abort(452,__FILE__." (".__LINE__.")");
      }
    }

    //Update the money of the user
    $userMoney -= $totalPrice;
    $query = "UPDATE user SET money = '$userMoney' WHERE uid = '$uid'";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,__FILE__." (".__LINE__.")");
    }
    commit($app['conn']);
    $app['userMoney'] = $userMoney;
    foreach ($_COOKIE as $key => $value) {
      if(preg_match("/\d+/",$key) === 1){
        setcookie($key,$value,time()-3600,'/');
      }
    }

    $twigParameters = getTwigParameters('Carrello',$app['siteName'],'checkout',$app['userMoney'],array('success' => "Dai un'occhiata alla tua rosa aggiornata!"));
    return $app['twig']->render('index.twig',$twigParameters);
  });
?>