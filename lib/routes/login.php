<?php
  use Symfony\Component\HttpFoundation\Request;

  $app->get('/login',function () use($app){
    if(isset($_SESSION['user']) && !empty($_SESSION['user']))
      return $app->redirect('//'.$app['request']->getHttpHost().'/home');

    $cmn = new Utils(DB::getInstance('root','','fantacalcio','localhost'));
    $cmn->initNavbar($app);
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

    $cmn = new Utils(DB::getInstance('root','','fantacalcio','localhost'));
    $cmn->initNavbar($app);
    $twigParameters = getTwigParameters('Login',$app['siteName'],'login',$app['userMoney'],$extra);
    return $app['twig']->render('index.twig',$twigParameters);
  });

?>