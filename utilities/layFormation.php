#!/usr/bin/php5.5-cli
<?php 
/*
  This script lays out the formation for those users who didn't make it in time.
  The algorithm is:
    0. Modulo is 3-4-3 or previously used one (if any)
    1. Check if match day is already on
    2. If player has no roster or roster is incomplete, quit. 
    3. Try to use the last used formation
    4. If not available, lay out the formation with the most expensive players
*/
    $dbhost = 'db591003352.db.1and1.com'; //Name of the db host
    $dbname = 'db591003352'; //Name of the db
    $dbusername = 'dbo591003352'; //Name of the db username
    $dbpsw = 'FArdzmrc90IO'; //Password of the db
//    $dbhost = 'localhost'; //Name of the db host
//    $dbname = 'fantacalcio'; //Name of the db
//    $dbusername = 'root'; //Name of the db username
//    $dbpsw = ''; //Password of the db

  $players_in_roster = 23;

  $conn = mysqli_connect($dbhost, $dbusername, $dbpsw, $dbname) or die(mysqli_error());

  $now = time();
  $query = "SELECT MID
            FROM match_day
            WHERE '$now' >= start AND '$now' <= end";
  $result = mysqli_query($conn,$query);
  if($result === false){
    echo mysqli_error($conn),"\n";
    return -1;
  }
  $row = mysqli_fetch_row($result);
  if(mysqli_affected_rows($conn) !== 0){//Match day is on
    $mid = $row[0];
    mysqli_query($conn,"START TRANSACTION");
    $query = "SELECT UID FROM user";
    $result = mysqli_query($conn,$query);
    if($result === false){
      echo mysqli_error($conn),"\n";
      mysqli_rollback($conn);
      return -1;
    }
    while(($row = mysqli_fetch_array($result,MYSQLI_NUM)) !== null){
      /*
        For each user, check if:
          1. he has a complete roster (if not skip)
          2. he has layed out the formation
      */
      $uid = $row[0];
      $query = "SELECT * FROM user_roster WHERE UID = '$uid'";
      $inner_result = mysqli_query($conn,$query);
      if(mysqli_affected_rows($conn) !== $players_in_roster){
        continue;
      }

      $query = "SELECT * FROM user_formation WHERE UID = '$uid' AND MID = '$mid'";
      $inner_result = mysqli_query($conn,$query);
      if($inner_result === false){
        echo mysqli_error($conn),"\n";
        mysqli_rollback($conn);
        return -1;
      }      
      if(mysqli_affected_rows($conn) === 0){ //User didn't lay out formation
        $prevMID = $mid - 1; //Avoid going below MID 1
        if($prevMID > 0){ //Put the last playing players
          $query = "SELECT * FROM user_formation WHERE UID = '$uid' AND MID = '$prevMID'";
          $inner_result = mysqli_query($conn,$query);
          if($inner_result === false){
            echo mysqli_error($conn),"\n";
            mysqli_rollback($conn);
            return -1;
          }
          while(($inner_row=mysqli_fetch_array($inner_result,MYSQLI_ASSOC)) !== null){
            $pk = getLastPrimaryKey($conn,'user_formation')+1;
            $spid = $inner_row['SPID'];
            $disposition = $inner_row['disposition'];
            $query = "INSERT INTO user_formation VALUES('$pk','$uid','$spid','$mid','$disposition')";
            $inner_result_2 = mysqli_query($conn,$query);
            if($inner_result_2 === false){
              echo mysqli_error($conn),"\n";
              mysqli_rollback($conn);
              return -1;
            }
          }
        }
        else{ //Put the most expensive players
          $query = "SELECT user_roster.SPID, Position as pos, Cost as cost
                    FROM user_roster, soccer_player
                    WHERE user_roster.SPID = soccer_player.SPID
                    AND user_roster.UID = '$uid' ORDER BY pos DESC, cost DESC";
          $inner_result = mysqli_query($conn,$query);
          if($inner_result === false){
            echo mysqli_error($conn),"\n";
            mysqli_rollback($conn);
            return -1;
          }
          $playingPlayers = array('POR'=>'',
                                  'DIF-1'=>'',
                                  'DIF-2'=>'',
                                  'DIF-3'=>'',
                                  'CEN-1'=>'',
                                  'CEN-2'=>'',
                                  'CEN-3'=>'',
                                  'CEN-4'=>'',
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
          while(($inner_row=mysqli_fetch_array($inner_result,MYSQLI_ASSOC)) !== null){
            switch ($inner_row['pos']) {
              case 'POR':
                if(empty($playingPlayers['POR'])){
                  $playingPlayers['POR'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['POR-R'])){
                  $playingPlayers['POR-R'] = $inner_row['SPID'];
                }
                break;
              case 'DIF':
                if(empty($playingPlayers['DIF-1'])){
                  $playingPlayers['DIF-1'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['DIF-2'])){
                  $playingPlayers['DIF-2'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['DIF-3'])){
                  $playingPlayers['DIF-3'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['DIF-R-1'])){
                  $playingPlayers['DIF-R-1'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['DIF-R-2'])){
                  $playingPlayers['DIF-R-2'] = $inner_row['SPID'];
                }
                break;
              case 'CEN':
                if(empty($playingPlayers['CEN-1'])){
                  $playingPlayers['CEN-1'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['CEN-2'])){
                  $playingPlayers['CEN-2'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['CEN-3'])){
                  $playingPlayers['CEN-3'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['CEN-4'])){
                  $playingPlayers['CEN-4'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['CEN-R-1'])){
                  $playingPlayers['CEN-R-1'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['CEN-R-2'])){
                  $playingPlayers['CEN-R-2'] = $inner_row['SPID'];
                }
                break;
              case 'ATT':
                if(empty($playingPlayers['ATT-1'])){
                  $playingPlayers['ATT-1'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['ATT-2'])){
                  $playingPlayers['ATT-2'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['ATT-3'])){
                  $playingPlayers['ATT-3'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['ATT-R-1'])){
                  $playingPlayers['ATT-R-1'] = $inner_row['SPID'];
                }
                elseif(empty($playingPlayers['ATT-R-2'])){
                  $playingPlayers['ATT-R-2'] = $inner_row['SPID'];
                }
                break;
              default:
                # Should never happen!
                break;
            } //END switch
          } //END while
          foreach ($playingPlayers as $role => $SPID) {
            $pk = getLastPrimaryKey($conn,'user_formation')+1;
            $query = "INSERT INTO user_formation VALUES('$pk','$uid','$SPID','$mid','$role')";
            $inner_result_2 = mysqli_query($conn,$query);
            if($inner_result_2 === false){
              echo mysqli_error($conn),"\n";
              mysqli_rollback($conn);
              return -1;
            }
          }
        } //END if-else lastFormation || most expensive players.
      }//END if layed out formation
    } //END while each user
    mysqli_commit($conn);
    return 0;
  }

  function getLastPrimaryKey($conn,$table,$primary='*'){
    $query = "SELECT $primary FROM $table";
    $result = mysqli_query($conn,$query);
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
?>