<?php 
  use Symfony\Component\HttpFoundation\Request;

  $app->before(function() use($app){
    if(isset($_COOKIE['token'])){
      $current_time = time();
      $sequence = explode(":", $_COOKIE['token']);
      $selector = $sequence[0];
      $token = $sequence[1];
      $query =  "SELECT token, expires, username
                FROM  auth_token, user
                WHERE selector='$selector'
                AND   auth_token.UID = user.UID";
      $result = getResult($app['conn'],$query);
      $row = mysqli_fetch_row($result);
      if(isset($row)){
        $sqlTime = $row[1];
        $expiration = date_create($sqlTime);
        $expiration = date_format($expiration,'U');
        if($expiration > $current_time){ //Cookie is still valid
          $sqlToken = $row[0];
          $token = hash('sha256',$token);
          if(hash_equals($sqlToken,$token)){ //Tokens match
            $username = $row[2];
            $_SESSION['user'] = $username;
          }
        }
      }
    }
    //If we are logged in but the userMoney is not set, set it!
    if(isset($_SESSION['user']) && !isset($app['userMoney'])){
      $user = $_SESSION['user'];
      $query = "SELECT money FROM user WHERE username = '$user'";
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");
      $row = mysqli_fetch_row($result);
      $app['userMoney'] = $row[0];
    }
    else
      $app['userMoney'] = -1;

    /*
      The time at which to close market and formation.
      Update if not defined or if expired.
    */ 
    if((!isset($app['closeTime']) && !isset($app['openTime'])) || $app['openTime'] <= time()){
      $now = time();
      $query = "SELECT start, end FROM match_day WHERE start >= '$now' LIMIT 1";
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");
      $row = mysqli_fetch_row($result);
      $app['closeTime'] = $row[0] - (60*5); //Close 5 minutes before
      $app['openTime'] = $row[1] + (60*10); //Open 10 minutes after
    }
  });
/*
**   ========================================================================
**  |                                  INDEX                                 |
**   ========================================================================
*/
  $app->get('/home',function() use($app){
    $query =  "SELECT username, points FROM user, scores WHERE user.UID = scores.UID ORDER BY points DESC";
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

/*
**   ========================================================================
**  |                               REGISTER                                 |
**   ========================================================================
*/
  $app->get('/register',function () use($app){
    if(isset($_SESSION['user']) && !empty($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/home');

    $twigParameters = getTwigParameters('Registrati',$app['siteName'],'register',$app['userMoney']);
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->post('/register',function(Request $req) use($app){
    $username = $req->get('username');
    $password = $req->get('password');
    $username = sanitizeInput($app['conn'],$username);
    $username = trim($username);
    $password = sanitizeInput($app['conn'],$password);
    $password = trim($password);
    if($username === '' || $password === '')
      $app->abort(460,"Sembra che qualche campo sia vuoto");

    if(($rv = begin($app['conn'])) !== true)
      $app->abort($rv->getCode(),$rv->getMessage());

    //Check if user is present
    $query =  "SELECT username FROM user WHERE username='$username'";
    $result = getResult($app['conn'],$query);
    $rowNum = mysqli_num_rows($result);
    if($rowNum != 0){
      rollback($app['conn']);
      $app->abort(461,"Username già in uso");
    }
    $password = password_hash($password,PASSWORD_BCRYPT);
    $uid = getLastPrimaryKey($app['conn'],'user')+1;
    $money = $app['startMoney'];
    $query = "INSERT INTO user VALUES('$uid','$username','$password','$money')";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,"Registration FAILED");
    }

    $sid = getLastPrimaryKey($app['conn'],'scores')+1;
    $query = "INSERT INTO scores VALUES('$sid','$uid','0')";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,"Registration FAILED");
    }
    commit($app['conn']);
    
    //Registration successful, set login info
    $_SESSION['user'] = $username;
    $extra = array('success' => 'La registrazione è stata effettuata con successo!');
    $twigParameters = getTwigParameters('Registrati',$app['siteName'],'register',$app['startMoney'],$extra);
    return $app['twig']->render('index.twig',$twigParameters);
  });

/*
**   ========================================================================
**  |                                  LOGIN                                 |
**   ========================================================================
*/
  $app->get('/login',function () use($app){
    if(isset($_SESSION['user']) && !empty($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/home');

    $twigParameters = getTwigParameters('Login',$app['siteName'],'login',$app['userMoney']);
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->post('/login',function (Request $req) use($app) {
    $username=$req->get('username');
    $password=$req->get('password');
    $username = sanitizeInput($app['conn'],$username);
    $username = trim($username);
    $password = sanitizeInput($app['conn'],$password);
    $password = trim($password);

    if($username === '' || $password === '')
      $app->abort(460,"Il campo username o password sembra vuoto");


    //Now search the DB for user and pass
    $query =  "SELECT UID, username, password, money FROM user WHERE username='$username'";
    $result = getResult($app['conn'],$query);
    $row = mysqli_fetch_row($result); //Returns null if user doesn't exist
    $sqlPsw = $row[2];
    $uid = $row[0];
    $app['userMoney'] = $row[3];

    if(!isset($row) || !password_verify($password,$sqlPsw)){ //If psw do not match or user doesn't exist
      $app->abort(462,"Username/password errati");
    }
    //Check if 'remember' is set
    $rememberMe = $req->get('remember');
    if($rememberMe === 'true'){ //Generate the cookie and add the selector:token to auth_token table
      $token = openssl_random_pseudo_bytes(33); //Generate the token, 33 chars long
      $selector = getRandomString(12); //Generate the selector, 12 chars long
      $time = time()+(30*24*60*60); //Cookie lasts 30 days
      setcookie("token",$selector.':'.$token,$time);

      $token = hash('sha256',$token);
      $pk = getLastPrimaryKey($app['conn'],'auth_token','AID')+1;
      $date = date('Y-m-d H:i:s',$time);//YYYY-MM-DD hh:mm:ss
      $query = "INSERT INTO auth_token VALUES('$pk','$selector','$token','$uid','$date')";
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");
    }

    $_SESSION['user'] = $username;
    $extra = array('success' => 'Schiera la tua formazione o compra giocatori da aggiungere alla tua rosa!');
    $twigParameters = getTwigParameters('Login',$app['siteName'],'login',$app['userMoney'],$extra);
    return $app['twig']->render('index.twig',$twigParameters);
  });

/*
**   ========================================================================
**  |                                  BUY                                   |
**   ========================================================================
*/
  $app->get('/buy',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');
    $now = time();
    if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
      $closeStart = date('d-m-y H:i',$app['closeTime']);
      $closeEnd = date('d-m-y H:i',$app['openTime']);
      $extra = array('warning' => 'Il mercato è chiuso dal '.$closeStart." al ".$closeEnd);
      $twigParameters = getTwigParameters('Acquista',$app['siteName'],'buy',$app['userMoney'],$extra);
    }
    else
      $twigParameters = getTwigParameters('Acquista',$app['siteName'],'buy',$app['userMoney']);      
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

    $twigParameters = getTwigParameters('Cerca',$app['siteName'],'buy',$app['userMoney'],array('players'=>$players));
    return $app['twig']->render('index.twig',$twigParameters);    
  });

  $app->get('/checkout',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');
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
      $twigParameters = getTwigParameters('Carrello',$app['siteName'],'checkout',$app['userMoney'],array('purchases' => $purchases));      
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

/*
**   ========================================================================
**  |                                 ROSTER                                 |
**   ========================================================================
*/
  $app->get('/roster',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');

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

/*
**   ========================================================================
**  |                              FORMATION                                 |
**   ========================================================================
*/
  /*
  **  Page render order:
  **    1. modulo.twig
  **    2. formation.twig
  */

  $app->get('/formation',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');
    $now = time();
    if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
      $closeStart = date('d-m-y H:i',$app['closeTime']);
      $closeEnd = date('d-m-y H:i',$app['openTime']);
      $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('warning' => 'Questa pagina è chiusa dal '.$closeStart." al ".$closeEnd));
    }
    else
      $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney']);
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->post('/formation',function(Request $req) use($app){
    $modulo = sanitizeInput($app['conn'],$req->get('mod'));
    $modulo = explode("-", $modulo);
    $formation['DIF']['mod'] = $modulo[0];
    $formation['CEN']['mod'] = $modulo[1];
    $formation['ATT']['mod'] = $modulo[2];

    $uid = getUID($app['conn'],$_SESSION['user']);

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
    }
    $modulo[0] -= 2;
    $modulo[1] -= 2;
    $modulo[2] -= 2;

//    if(($rv = begin($app['conn'])) !== true)
//      $app->abort($rv->getCode(),$rv->getMessage());
//    //Set the players as playing
//    foreach ($reqItems as $role => $SPID) {
//          # code...
//        }    

    myDump($reqItems);
  });
/*
**   ========================================================================
**  |                                 LOGOUT                                 |
**   ========================================================================
*/
  $app->get('/logout',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');
    closeSession($app);
    $extra = array('success' => 'Logout effettuato!');
    $twigParameters = getTwigParameters('Logout',$app['siteName'],'logout',$app['userMoney'],$extra);
    return $app['twig']->render('index.twig',$twigParameters);
  });

  $app->get('/rules',function () use($app){
    $twigParameters = getTwigParameters('Regolamento',$app['siteName'],'rules',$app['userMoney']);
    return $app['twig']->render('index.twig',$twigParameters);    
  });
?>