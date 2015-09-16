<?php 
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
?>