<?php 
  use Symfony\Component\HttpFoundation\Request;
  include_once '../vendor/phpQuery/phpQuery-onefile.php';

  function rememberMe($app){
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
  }

  function userMoney($app){
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
  }

  function closingTime($app){
    /*
      The time at which to close market and formation.
      Update if not defined or if expired.
    */ 
    if((!isset($app['closeTime']) && !isset($app['openTime'])) || $app['openTime'] <= time()){
      $now = time();
      $query = "SELECT start, end 
                FROM match_day
                WHERE MID = (
                  SELECT MIN(MID)
                  FROM match_day 
                  WHERE end >= '$now')";
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");
      $row = mysqli_fetch_row($result);
      $app['closeTime'] = $row[0] - (60*5); //Close 5 minutes before
      $app['openTime'] = $row[1] + (60*15); //Open 15 minutes after
    }    
  }

  function fetchMarks($app){
    $now = time();
    $outcome = false; //Indicates if the attempt to fetch the results succeeded
    //Check if the marks are already in DB. Fetch only the last marks
    $query = "SELECT * FROM player_mark
              WHERE player_mark.MID = (
                SELECT MAX(MID) FROM match_day
                WHERE match_day.end <= '$now')";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");

    if(mysqli_affected_rows($app['conn']) === 0){ //Results are still not out, attempt fetch
      $marksPage = 'http://www.gazzetta.it/calcio/fantanews/voti/serie-a-2015-16/';
      ini_set("user_agent", "Descriptive user agent string");
      $htmlPage = file_get_contents($marksPage);
      $doc = phpQuery::newDocument($htmlPage);
      
      //Get the matchday
      $mid = pq($doc['ul.menuDaily li.active'])->children('a')->text();
      //Check if it's the right match day
      $query = "SELECT MAX(MID) FROM match_day
                WHERE match_day.end <= '$now'";
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");
      $sqlMid = mysqli_fetch_row($result);
      $sqlMid = $sqlMid[0];

      //If the matchDay from gazzetta is the same we want, fetch!
      if($mid === $sqlMid){
        if(($rv = begin($app['conn'])) !== true)
          $app->abort($rv->getCode(),$rv->getMessage());

        foreach (pq($doc['div.magicDayList.matchView.magicDayListChkDay:not(."forceHide") ul.magicTeamList li:not(".head")']) as $row) {    
          //Get the Name and ID
          $link = pq($row)->find('span.playerNameIn')->children('a')->attr('href');
          $link = explode('/',$link);
          //Divide name from ID
          $name_and_id = explode('_',$link[count($link)-1]);
          //Save the SPID
          $SPID = sanitizeInput($app['conn'],$name_and_id[count($name_and_id)-1]);
          $mark = sanitizeInput($app['conn'],pq($row)->find('div.inParameter.fvParameter')->text());
          
          $pk = getLastPrimaryKey($app['conn'],'player_mark')+1;
          $query = "INSERT INTO player_mark VALUES ('$pk','$SPID','$mid','$mark')";
          $result = getResult($app['conn'],$query);
          if($result === false){
            rollback($app['conn']);
            $app->abort(452,__FILE__." (".__LINE__.")");
          }
          $outcome = true;
        }
        commit($app['conn']);
      }
    }
    else
      $outcome=true; //Marks are out
    return $outcome;
  }

  function computeResults($app){

    /*
      Select all the UIDs whose MID is lower than the current one,
      i.e those whose score is still to be computed
    */
    $now = time();
    $query = "SELECT UID FROM scores
              WHERE MID < (
                SELECT MAX(MID)
                FROM match_day
                WHERE match_day.end <= '$now')";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $UIDs = array();
    while(($row=mysqli_fetch_array($result,MYSQLI_NUM)) !== null)
      array_push($UIDs, $row[0]);

    if(mysqli_affected_rows($app['conn']) !== 0){ //There are results to compute

      if(($rv = begin($app['conn'])) !== true)
        $app->abort($rv->getCode(),$rv->getMessage());

      //For every UID that need scores to be computed
      for ($i=0; $i < count($UIDs); $i++) { 
        $uid = $UIDs[$i];
        /*
          Select the list of the soccer players the user put in his formation.
          LEFT OUTER JOINs are needed because not all soccer players in the formation
          actually played in real life, so they may not have a mark 
        */
        $query = "SELECT player_mark.mark as mark, user_formation.disposition as role
                  FROM soccer_player
                    LEFT OUTER JOIN player_mark ON player_mark.SPID=soccer_player.SPID
                    LEFT OUTER JOIN user_formation ON user_formation.SPID = soccer_player.SPID
                    LEFT OUTER JOIN match_day ON match_day.MID = player_mark.MID
                  WHERE user_formation.UID = '$uid'
                  AND user_formation.MID = (
                    SELECT MAX(MID) FROM match_day
                    WHERE match_day.end <= '$now')";
        $result = getResult($app['conn'],$query);
        if($result === false){
          rollback($app['conn']);
          $app->abort(452,__FILE__." (".__LINE__.")");
        }
        while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null)
          $marks[$row['role']] = (float)$row['mark'];

        $total = 0;
        if(isset($marks)){ //$marks is set only if user made a formation
          if($marks['POR'] === (float)0 ){
            $total += $marks['POR-R'];
            unset($marks['POR-R']);
          }
          else
            $total += $marks['POR'];

          if($marks['DIF-1'] === (float)0){
            if(isset($marks['DIF-R-1']) && $marks['DIF-R-1'] !== 0 ){
              $total += $marks['DIF-R-1'];
              unset($marks['DIF-R-1']);
            }
            elseif(isset($marks['DIF-R-2']) && $marks['DIF-R-2'] !== 0){
              $total += $marks['DIF-R-2'];
              unset($marks['DIF-R-2']);
            }
          }
          else
            $total += $marks['DIF-1'];

          if($marks['DIF-2'] === (float)0){
            if(isset($marks['DIF-R-1']) && $marks['DIF-R-1'] !== 0 ){
              $total += $marks['DIF-R-1'];
              unset($marks['DIF-R-1']);
            }
            elseif(isset($marks['DIF-R-2']) && $marks['DIF-R-2'] !== 0){
              $total += $marks['DIF-R-2'];
              unset($marks['DIF-R-2']);
            }
          }
          else
            $total += $marks['DIF-2'];

          if($marks['DIF-3'] === (float)0){
            if(isset($marks['DIF-R-1']) && $marks['DIF-R-1'] !== 0 ){
              $total += $marks['DIF-R-1'];
              unset($marks['DIF-R-1']);
            }
            elseif(isset($marks['DIF-R-2']) && $marks['DIF-R-2'] !== 0){
              $total += $marks['DIF-R-2'];
              unset($marks['DIF-R-2']);
            }
          }
          else
            $total += $marks['DIF-3'];

          if(isset($marks['DIF-4'])){
            if($marks['DIF-4'] === (float)0){
              if(isset($marks['DIF-R-1']) && $marks['DIF-R-1'] !== 0 ){
                $total += $marks['DIF-R-1'];
                unset($marks['DIF-R-1']);
              }
              elseif(isset($marks['DIF-R-2']) && $marks['DIF-R-2'] !== 0){
                $total += $marks['DIF-R-2'];
                unset($marks['DIF-R-2']);
              }
            }
            else
              $total += $marks['DIF-4'];
          }

          if(isset($marks['DIF-5'])){
            if($marks['DIF-5'] === (float)0){
              if(isset($marks['DIF-R-1']) && $marks['DIF-R-1'] !== 0 ){
                $total += $marks['DIF-R-1'];
                unset($marks['DIF-R-1']);
              }
              elseif(isset($marks['DIF-R-2']) && $marks['DIF-R-2'] !== 0){
                $total += $marks['DIF-R-2'];
                unset($marks['DIF-R-2']);
              }
            }
            else
              $total += $marks['DIF-5'];
          }

          if($marks['CEN-1'] === (float)0){
            if(isset($marks['CEN-R-1']) && $marks['CEN-R-1'] !== 0 ){
              $total += $marks['CEN-R-1'];
              unset($marks['CEN-R-1']);
            }
            elseif(isset($marks['CEN-R-2']) && $marks['CEN-R-2'] !== 0){
              $total += $marks['CEN-R-2'];
              unset($marks['CEN-R-2']);
            }
          }
          else
            $total += $marks['CEN-1'];

          if($marks['CEN-2'] === (float)0){
            if(isset($marks['CEN-R-1']) && $marks['CEN-R-1'] !== 0 ){
              $total += $marks['CEN-R-1'];
              unset($marks['CEN-R-1']);
            }
            elseif(isset($marks['CEN-R-2']) && $marks['CEN-R-2'] !== 0){
              $total += $marks['CEN-R-2'];
              unset($marks['CEN-R-2']);
            }
          }
          else
            $total += $marks['CEN-2'];

          if($marks['CEN-3'] === (float)0){
            if(isset($marks['CEN-R-1']) && $marks['CEN-R-1'] !== 0 ){
              $total += $marks['CEN-R-1'];
              unset($marks['CEN-R-1']);
            }
            elseif(isset($marks['CEN-R-2']) && $marks['CEN-R-2'] !== 0){
              $total += $marks['CEN-R-2'];
              unset($marks['CEN-R-2']);
            }
          }
          else
            $total += $marks['CEN-3'];

          if(isset($marks['CEN-4'])){
            if($marks['CEN-4'] === (float)0){
              if(isset($marks['CEN-R-1']) && $marks['CEN-R-1'] !== 0 ){
                $total += $marks['CEN-R-1'];
                unset($marks['CEN-R-1']);
              }
              elseif(isset($marks['CEN-R-2']) && $marks['CEN-R-2'] !== 0){
                $total += $marks['CEN-R-2'];
                unset($marks['CEN-R-2']);
              }
            }
            else
              $total += $marks['CEN-4'];
          }

          if(isset($marks['CEN-5'])){
            if($marks['CEN-5'] === (float)0){
              if(isset($marks['CEN-R-1']) && $marks['CEN-R-1'] !== 0 ){
                $total += $marks['CEN-R-1'];
                unset($marks['CEN-R-1']);
              }
              elseif(isset($marks['CEN-R-2']) && $marks['CEN-R-2'] !== 0){
                $total += $marks['CEN-R-2'];
                unset($marks['CEN-R-2']);
              }
            }
            else
              $total += $marks['CEN-5'];
          }

          if($marks['ATT-1'] === (float)0){
            if(isset($marks['ATT-R-1']) && $marks['ATT-R-1'] !== 0 ){
              $total += $marks['ATT-R-1'];
              unset($marks['ATT-R-1']);
            }
            elseif(isset($marks['ATT-R-2']) && $marks['ATT-R-2'] !== 0){
              $total += $marks['ATT-R-2'];
              unset($marks['ATT-R-2']);
            }
          }
          else
            $total += $marks['ATT-1'];

          if(isset($marks['ATT-2'])){
            if($marks['ATT-2'] === (float)0){
              if(isset($marks['ATT-R-1']) && $marks['ATT-R-1'] !== 0 ){
                $total += $marks['ATT-R-1'];
                unset($marks['ATT-R-1']);
              }
              elseif(isset($marks['ATT-R-2']) && $marks['ATT-R-2'] !== 0){
                $total += $marks['ATT-R-2'];
                unset($marks['ATT-R-2']);
              }
            }
            else
              $total += $marks['ATT-2'];
          }

          if(isset($marks['ATT-3'])){
            if($marks['ATT-3'] === (float)0){
              if(isset($marks['ATT-R-1']) && $marks['ATT-R-1'] !== 0 ){
                $total += $marks['ATT-R-1'];
                unset($marks['ATT-R-1']);
              }
              elseif(isset($marks['ATT-R-2']) && $marks['ATT-R-2'] !== 0){
                $total += $marks['ATT-R-2'];
                unset($marks['ATT-R-2']);
              }
            }
            else
              $total += $marks['ATT-3'];
          }
        }

        //Update scores, also if the user has no formation ($total will be 0)
        $query = "UPDATE scores
                  SET points = points + '$total', MID = MID + 1
                  WHERE UID = '$uid'";
        $result = getResult($app['conn'],$query);
        if($result === false){
          rollback($app['conn']);
          $app->abort(452,__FILE__." (".__LINE__.")");
        }
        unset($marks);
      } //End for loop
      commit($app['conn']);
    } //End insert scores
  }

  $app->before(function() use($app){
    rememberMe($app);
    userMoney($app);
    closingTime($app);
    if(fetchMarks($app))
      computeResults($app); //Compute the results for each user only if marks are out
  });
/*
**   ========================================================================
**  |                                  INDEX                                 |
**   ========================================================================
*/
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
    $repeatPsw = $req->get('repeat-password');
    $invite = $req->get('invite-code');
    $username = sanitizeInput($app['conn'],$username);
    $username = trim($username);
    $password = sanitizeInput($app['conn'],$password);
    $password = trim($password);
    $repeatPsw = sanitizeInput($app['conn'],$repeatPsw);
    $repeatPsw = trim($repeatPsw);
    $invite = sanitizeInput($app['conn'],$invite);
    $invite = trim($invite);
    
    if($username === '' || $password === '' || $invite === '')
      $app->abort(460,"Sembra che qualche campo sia vuoto");
    
    if($password !== $repeatPsw)
      $app->abort(452,"La password non coincide!");
    
    if(strlen($username) < 3 || strlen($username) > 15 || strlen($password) < 6)
      $app->abort(463,"Sembra che qualche campo non rispetti la lunghezza richiesta");
    
    if(($test=preg_match('/^[A-Za-z]/', $username)) === false){
      $app->abort(452,"Registration FAILED");
    }
    elseif ($test === 0) {
      $app->abort(464,"Sembra che il nome utente non inizi con una lettera");
    }
    
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

    $query = "SELECT * FROM invite_code WHERE code = '$invite' FOR UPDATE";
    $result = getResult($app['conn'],$query);
    if(mysqli_affected_rows($app['conn']) !== 1){
      rollback($app['conn']);
      $app->abort(452,"Codice invito errato");
    }
    $query = "DELETE FROM invite_code WHERE code='$invite'";
    $result= getResult($app['conn'],$query);

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
    $now = time();
    $query = "SELECT MAX(MID) FROM match_day WHERE match_day.end <= '$now'";
    $result = getResult($app['conn'],$query);
    if($result === false){
      rollback($app['conn']);
      $app->abort(452,"Registration FAILED");
    }
    $mid = mysqli_fetch_row($result);
    $mid = $mid[0]; //Insert into the score the last MID
    $query = "INSERT INTO scores VALUES('$sid','$uid','$mid','0')";
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
      //Delete all the data in user_formation of the user
      $query = "DELETE FROM user_formation
                WHERE UID = '$uid'";
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

/*
**   ========================================================================
**  |                              FORMATION                                 |
**   ========================================================================
*/
  /*
  **  Page render order:
  **    1. modulo.twig
  **    2. formation.twig
  **    3. confirmForm.twig
  */

  $app->get('/formation',function () use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');
    $now = time();
    if($now >= $app['closeTime'] && $now < $app['openTime']){ //Market is closed!
      $closeStart = date('d-m-y H:i',$app['closeTime']);
      $closeEnd = date('d-m-y H:i',$app['openTime']);
    }
    $uid = getUID($app['conn'],$_SESSION['user']);
    $query = "SELECT COUNT(*) FROM user_roster WHERE UID = '$uid'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $row = mysqli_fetch_row($result);
    if((int)$row[0] !== 23){ //Roster incomplete
      $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('error' => 'Sembra che la tua rosa sia incompleta'));
    }
    else{
      //Show the soccer players that play
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
                  SELECT MID FROM match_day
                  WHERE match_day.start < '$now' AND match_day.end > '$now')";
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
          $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('playingPlayers'=>$playingPlayers,'warning' => 'Questa pagina è bloccata dal '.$closeStart.' al '.$closeEnd));        
        else
          $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('playingPlayers'=>$playingPlayers,'closeTime'=>date('d-m-y H:i',$app['closeTime'])));
      }
      else{
        if(isset($closeStart) && isset($closeEnd)) //If market is closed
          $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('warning' => 'Questa pagina è bloccata dal '.$closeStart.' al '.$closeEnd));
        else
          $twigParameters = getTwigParameters('Formazione',$app['siteName'],'modulo',$app['userMoney'],array('closeTime'=>date('d-m-y H:i',$app['closeTime'])));
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
/*
**   ========================================================================
**  |                                 MARKS                                  |
**   ========================================================================
*/

  $app->get('/marks',function() use($app){
    if(!isset($_SESSION['user']))
      return $app->redirect(dirname($_SERVER['REQUEST_URI']).'/login');

    /*
      Get the marks of the LAST match day from DB for each soccer player
      belonging to the user.
      LEFT OUTER JOINS are needed because the player in player_mark are not the same
      as the ones in user_formation.
      In the output table we want also the soccer players that have no mark because
      either they didn't play enough minutes or they didn't played at all.
      Players that played less than 15' (or 25' if goalkeeper) will have a 0.
      Players that didn't play at all will have a NULL value.
    */
    $uid = getUID($app['conn'],$_SESSION['user']);
    $now = time();
    $query = "SELECT soccer_player.Name as name, player_mark.mark as mark, user_formation.disposition as role
              FROM soccer_player
                LEFT OUTER JOIN player_mark ON player_mark.SPID=soccer_player.SPID
                LEFT OUTER JOIN user_formation ON user_formation.SPID = soccer_player.SPID
                LEFT OUTER JOIN match_day ON match_day.MID = player_mark.MID
              WHERE user_formation.UID = '$uid'
              AND user_formation.MID = (
                SELECT MAX(MID) FROM match_day
                WHERE match_day.end <= '$now')
              AND player_mark.MID = (
                SELECT MAX(MID) FROM match_day
                WHERE match_day.end <= '$now')";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
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
    while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null){
      switch ($row['role']) {
        case 'POR':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-1':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-2':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-3':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-4':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-5':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-1':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-2':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-3':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-4':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-5':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'ATT-1':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'ATT-2':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'ATT-3':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'POR-R':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-R-1':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'DIF-R-2':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-R-1':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'CEN-R-2':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'ATT-R-1':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        case 'ATT-R-2':
          $playerMarks[$row['role']] = array('name'=>$row['name'],'mark'=>$row['mark']);
          break;
        default:
          # Should never happen
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
    $twigParameters = getTwigParameters('Regolamento',$app['siteName'],'rules',$app['userMoney'],array('startMoney'=>$app['startMoney']));
    return $app['twig']->render('index.twig',$twigParameters);    
  });
?>