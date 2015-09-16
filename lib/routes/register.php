<?php
  use Symfony\Component\HttpFoundation\Request;

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
?>