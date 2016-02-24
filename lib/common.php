<?php
  include_once '../vendor/phpQuery/phpQuery-onefile.php';
/*
** ===================================================
**|                 Session Functions                 |
** ===================================================
*/
  function closeSession($app){
    if(isset($_COOKIE['token'])){ //Destroy the token
      $cookie_array = explode(":",$_COOKIE['token']);
      $selector = $cookie_array[0];
      $query = "SELECT * FROM auth_token WHERE selector='$selector' FOR UPDATE";
      $result = getResult($app['conn'],$query);
      if($result === false){
        $app->abort(452,__FILE__." (".__LINE__.")");
      }
      $query = "DELETE FROM auth_token WHERE selector='$selector'";
      $result = getResult($app['conn'],$query);
      if($result === false){
        $app->abort(452,__FILE__." (".__LINE__.")");        
      }
      setcookie('token','',time()-3600);
    }
    session_unset();
    if(session_id() != "" || isset($_COOKIE[session_name()]))
      setcookie(session_name(), '', time()-3600, '/');
    session_destroy();
  }

/*
** ===================================================
**|                   MySQL Functions                 |
** ===================================================
*/
  function getResult($conn,$query){
    return mysqli_query($conn,$query);
  }

  function begin($conn){
    /*
    **  Wrap SQL instructions for beginnig transaction inside a try block.
    **  If an exception is raised, return the exception.
    **  Otherwise return true.
    */
    try{
      $ret = mysqli_query($conn, "START TRANSACTION");
      if(!$ret)
        throw new Exception("MYSQL error START TRANSACTION", 452);
    } catch (Exception $e){
      return $e;
    }
    return true;
  }

  function commit($conn){
    return mysqli_commit($conn);
  }

  function rollback($conn){
    return mysqli_rollback($conn);
  }

  /*
  **  This function returns the primary key of the last row of the requested table
  **  This is needed because apparently there's no way to fetch the last primary key
  **  In case of a table with primary key made of multiple attributes, define
  **  $primary as you call the function.
  */
  function getLastPrimaryKey($conn,$table,$primary='*'){
    $query = "SELECT $primary FROM $table";
    $result = getResult($conn,$query);
    while (($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) != false)
      $last = $row;
    //If we are not looking for a specific attribute,
    if($primary==='*' && isset($last)){
      //Get the values into an indexed array (non-associative)
      $vals=array_values($last);
      //Return the first element
      return isset($vals)? $vals[0] : 0;
    }
    else
      return isset($last)? $last[$primary] : 0;
  }

  function getUID($conn,$user){
    $query = "SELECT UID FROM user WHERE username='$user'";
    $result = getResult($conn,$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    $row = mysqli_fetch_row($result);
    return $row[0];
  }

  function updateAuth_tokenTable($app){
    $currentTime = time();
    $currentDate = date('Y-m-d H:i:s',$currentTime);
    $query = "SELECT * FROM auth_token WHERE expires < '$currentDate'";
    $result = getResult($app['conn'],$query);
    if($result === false)
      $app->abort(452,__FILE__." (".__LINE__.")");
    if(mysqli_num_rows($result) !== 0){ //Something to delete
      $query = "DELETE FROM auth_token WHERE expires < '$currentDate'";
      
      $result = getResult($app['conn'],$query);
      if($result === false)
        $app->abort(452,__FILE__." (".__LINE__.")");

    }
  }

  //Generates a random string, used as selector for auth_token
  function getRandomString($length = 12,$specials=true) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if($specials)
      $characters.='|!£$%&/()=?^-_<>';
    $string = '';

    for($i = 0; $i < $length; $i++)
      $string .= $characters[mt_rand(0, strlen($characters) - 1)];

    return $string;
  }

/*
** ===================================================
**|               Sanitizing Functions                |
** ===================================================
*/
  function sanitizeInput($conn,$in){
    $in = strip_tags($in);    //Removes html tags (i.e <p>Text</p> becomes Text)
    $in = htmlentities($in);  //Converts html entities into their corresponding ASCII code (i.e < becomes &lt;)
    $in = stripslashes($in);  //Removes escaping chars like \
    return mysqli_real_escape_string($conn,$in);  //Escapes special characters like NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.  
  }

/*
** ===================================================
**|       Menu and Rendering Functions                |
** ===================================================
*/

  function is_assoc($ar){
    // Keys of the array
    $keys = array_keys($ar);

    // If the array keys of the keys match the keys, then the array must
    // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
    return array_keys($keys) !== $keys;
  }

  function getTwigParameters($pageName,$siteName,$pageMenuRender,$userMoney,$extra=array()){
    $paramArray = array(  'pageName'      =>  $pageName,
                          'siteName'      =>  $siteName,
                          'twigTemplate'  =>  $pageMenuRender,
                          'userMoney'     =>  $userMoney,
                          'parameters'    =>  array_merge(array('error'=>'','success' =>'','warning'=>''),$extra)
                        );
    if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
      $paramArray['username'] = $_SESSION['user'];
    }
    return $paramArray;
  }

/*
** ===================================================
**|              Config.json Functions                |
** ===================================================
*/

  function generateConfig($app){
    updateAuth_tokenTable($app);
    $configFile['last-update'] = date('Y-m-d H:i:s',time());
    $configFile['start-money'] = 260;
    $configFile['maintenance'] = false;
    $app['startMoney'] = $configFile['start-money'];
    $app['maintenance'] = $configFile['maintenance'];
    $fp = fopen($app['configFilePath'],"w");
    fwrite($fp,json_encode($configFile));
    fclose($fp);
  }

  function updateConfig($app){
    updateAuth_tokenTable($app);
    $currentTime = time();
    $configFile = file_get_contents($app['configFilePath']);
    $configFile = json_decode($configFile,true);
    
    $app['startMoney'] = $configFile['start-money'];
    $app['maintenance'] = $configFile['maintenance'];
    $lastUpdate = date_create($configFile['last-update']);
    $lastUpdate = date_format($lastUpdate,'U');
    $fiveDays = (5*24*60*60);
    //Update just the date
    if(($lastUpdate+$fiveDays) <= $currentTime){
      $configFile['last-update'] = date('Y-m-d H:i:s',time());
      $fp = fopen($app['configFilePath'],"w");
      fwrite($fp,json_encode($configFile));
      fclose($fp);
    }
  }

/*
** ===================================================
**|        Football Data API Functions                |
** ===================================================
*/

  function getSoccerData($app,$link){
    $uri = 'http://api.football-data.org/alpha/'.$link;
    $header = array('headers' => array('X-Auth-Token' => $app['apiKey']));
    $response = $app['guzzle']->get($uri, $header);          
    return json_decode($response->getBody(),true);  
  }

  function myDump($var){
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
  }

  function customSort(&$array){
    usort($array, "cmpFunction");
    foreach ($array as &$entry) {
      switch ($entry['role']) {
        case '0':
          $entry['role'] = 'POR';
          break;
        case '1':
          $entry['role'] = 'DIF';
          break;
        case '2':
          $entry['role'] = 'CEN';
          break;
        case '3':
          $entry['role'] = 'ATT';
          break;
        default:
          # Should never happen
          break;
      }
    }
  }
  //This orders players by 1. Role, 2. Price
  function cmpFunction($a,$b){
    if($a['role'] < $b['role']){ //Roles are 0-> POR, 1-> DIF, 2-> CEN, 3->ATT
      return -1;
    }
    if($a['role'] > $b['role']){
      return 1;
    }
    if($a['role'] === $b['role']){
      if($a['price'] < $b['price']){
        return -1;
      }
      if($a['price'] > $b['price']){
        return 1;
      }
      if($a['price'] === $b['price']){
        return 0;
      }
    }
  }

?>