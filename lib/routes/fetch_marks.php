<?php
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
?>