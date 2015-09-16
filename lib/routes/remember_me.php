<?php 
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
?>