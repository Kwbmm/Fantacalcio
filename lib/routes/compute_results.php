<?php
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
        //Select all the players in the user formation for the last played MID
        $query = "SELECT disposition as role
                  FROM user_formation
                  WHERE user_formation.UID = '$uid'
                  AND user_formation.MID = (
                    SELECT MAX(MID)
                    FROM match_day
                    WHERE match_day.end <= '$now')";
        $result = getResult($app['conn'],$query);
        if($result === false){
          rollback($app['conn']);
          $app->abort(452,__FILE__." (".__LINE__.")");
        }
        while (($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) !== null)
          $formation[$row['role']] = 0;

        //Select the player marks of the last played MID, for the user
        $query = "SELECT disposition as role, mark
                  FROM player_mark, user_formation
                  WHERE player_mark.SPID = user_formation.SPID
                  AND user_formation.UID = '$uid'
                  AND player_mark.MID = (
                    SELECT MAX(MID)
                    FROM match_day
                    WHERE match_day.end <= '$now')
                  AND user_formation.MID = (
                    SELECT MAX(MID)
                    FROM match_day
                    WHERE match_day.end <= '$now')";
        $result = getResult($app['conn'],$query);
        if($result === false){
          rollback($app['conn']);
          $app->abort(452,__FILE__." (".__LINE__.")");
        }
        while (($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) !== null)
          $marks[$row['role']] = (float)$row['mark'];
        $notPlayed = array_diff_key($formation,$marks);

        foreach ($notPlayed as $role => $mark)
          $marks[$role] = (float)0;

        myDump($marks);
        $total = (float)0;
        if(isset($marks)){ //$marks is set only if user made a formation
          if($marks['POR'] === (float)0 ){
            $total += $marks['POR-R'];
            unset($marks['POR-R']);
          }
          else
            $total += $marks['POR'];
          myDump("POR");
          myDump($total);

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
          myDump("DIF-1");
          myDump($total);

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
          myDump("DIF-2");
          myDump($total);

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
          myDump("DIF-3");
          myDump($total);

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
          myDump("DIF-4");
          myDump($total);
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
          myDump("DIF-5");
          myDump($total);
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
          myDump("CEN-1");
          myDump($total);

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
          myDump("CEN-2");
          myDump($total);

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
          myDump("CEN-3");
          myDump($total);

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
          myDump("CEN-4");
          myDump($total);
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
          myDump("CEN-5");
          myDump($total);
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
          myDump("ATT-1");
          myDump($total);

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
          myDump("ATT-2");
          myDump($total);
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
          myDump("ATT-3");
          myDump($total);
          }
        }
        myDump("TOTAL");
        myDump($total);

        //Update scores, also if the user has no formation ($total will be 0)
        $query = "UPDATE scores
                  SET points = points + '$total', MID = (
                    SELECT MAX(MID) FROM match_day
                    WHERE match_day.end <= '$now')
                  WHERE UID = '$uid'";
        $result = getResult($app['conn'],$query);
        if($result === false){
          rollback($app['conn']);
          $app->abort(452,__FILE__." (".__LINE__.")");
        }
        unset($marks);
        unset($notPlayed);
        unset($formation);
      } //End for loop
      commit($app['conn']);
    } //End insert scores
  }
?>