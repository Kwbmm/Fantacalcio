<?php
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
?>