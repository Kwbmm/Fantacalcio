<?php
  require_once '../vendor/phpQuery/phpQuery-onefile.php';

  $dbhost = 'db591003352.db.1and1.com'; //Name of the db host
  $dbname = 'db591003352'; //Name of the db
  $dbusername = 'dbo591003352'; //Name of the db username
  $dbpsw = 'FArdzmrc90IO'; //Password of the db
  $conn = mysqli_connect($dbhost, $dbusername, $dbpsw, $dbname) or die(mysqli_error());

  $statsPage = 'http://www.gazzetta.it/calcio/fantanews/statistiche/serie-a-2015-16/';

  ini_set("user_agent", "Descriptive user agent string");
  $htmlPage = file_get_contents($statsPage);
  $doc = phpQuery::newDocument($htmlPage);

  //Get Soccer Player Team, ID, Name, Role and Price 
  $updatedPlayers = array();
  foreach (pq($doc['table.playerStats tbody tr']) as $row) {
    $player = array();

    //Get the Name and ID
    $link = pq($row)->children('td.field-giocatore')->children('a')->attr('href');
    $link = explode('/',$link);
    //Divide name from ID
    $name_and_id = explode('_',$link[count($link)-1]);
    //Fill players array with SPID and Name
    for ($i=0; $i < count($name_and_id); $i++) { 
      if($i === count($name_and_id)-1){ //It's the ID of the player
        $player['SPID'] = $name_and_id[$i];
      } 
      else{
        if(!isset($player['name']))
          $player['name'] = ucfirst($name_and_id[$i]);
        else
          $player['name'] .= ' '.ucfirst($name_and_id[$i]); 
      }
    }

    //Get the squad
    $squad = pq($row)->children('td.field-sqd')->children('span.hidden-team-name')->text();
    $player['team'] = ucfirst($squad);

    //Get the Role
    $role = pq($row)->children('td.field-ruolo')->text();
    //Role can be P, D, C, A, T (C), T(A);
    //We remove the T and just get what's inside the parenthesis
    $player['role'] = preg_replace('/(\w) \((\w)\)/','${2}',$role);
    switch ($player['role']) {
      case 'P':
        $player['role'] = 'POR';
        break;
      case 'D':
        $player['role'] = 'DIF';
        break;
      case 'C':
        $player['role'] = 'CEN';
        break;
      case 'A':
        $player['role'] = 'ATT';
        break;        
      default:
        # Should never happen
        break;
    }
    
    //Get the price
    $player['price'] = pq($row)->children('td.field-q')->text();
    $updatedPlayers[$player['SPID']] = array('name'=>$player['name'],'team'=>$player['team'],'role'=>$player['role'],'price'=>$player['price']);
  }

  //Get the SPID players from DB
  $query = "SELECT SPID,Name FROM soccer_player";
  $result = mysqli_query($conn,$query);
  if($result === false){
    echo mysqli_error($conn);
    return -1;
  }
  $dbPlayers = array();
  while(($row = mysqli_fetch_array($result,MYSQLI_NUM))!== null)
    $dbPlayers[$row[0]] = $row[1];

  $notInDB = array_diff_key($updatedPlayers, $dbPlayers);
  $toUpdate = array_intersect_key($updatedPlayers, $dbPlayers);
  $toRemoveFromDB = array_diff_key($dbPlayers, $updatedPlayers);

  mysqli_query($conn,"START TRANSACTION");
  //First remove the players that are not present anymore
  foreach ($toRemoveFromDB as $key => $name) {
    $query = "SELECT Cost as cost, user_roster.UID as UID
              FROM user_roster, soccer_player
              WHERE user_roster.SPID = '$key'
              AND user_roster.SPID = soccer_player.SPID
              FOR UPDATE";
    $result = mysqli_query($conn,$query);
    if($result === false){
      mysqli_rollback($conn);
      echo mysqli_error($conn);
      return -1;
    }
    $row = mysqli_fetch_assoc($result);
    if(!empty($row)){
      $uid = $row['UID'];
      $add = $row['cost'];
      $query = "UPDATE user SET money = money + '$add' WHERE UID = '$uid'";
      $result = mysqli_query($conn,$query);
      if($result === false){
        mysqli_rollback($conn);
        echo mysqli_error($conn);
        return -1;
      }
      //Delete from user_roster
      $query = "DELETE FROM user_roster
                WHERE user_roster.SPID = '$key'";
      $result = mysqli_query($conn,$query);
      if($result === false){
        mysqli_rollback($conn);
        echo mysqli_error($conn);
        return -1;
      }              
    }
    //Delete from soccer_player
    $query = "DELETE FROM soccer_player
              WHERE soccer_player.SPID = '$key'";
    $result = mysqli_query($conn,$query);
    if($result === false){
      mysqli_rollback($conn);
      echo mysqli_error($conn);
      return -1;
    }
  }
  //Update the players in DB
  foreach ($toUpdate as $key => $value) {
    $name = $value['name'];
    $team = $value['team'];
    $role = $value['role'];
    $price = $value['price'];

    $query = "SELECT * FROM soccer_player
              WHERE SPID = '$key'
              FOR UPDATE";
    $result = mysqli_query($conn,$query);
    if($result === false){
      mysqli_rollback($conn);
      echo mysqli_error($conn);
      return -1;
    }
    $query = "UPDATE soccer_player
              SET Name = '$name', Position = '$role', Team='$team', Cost = '$price'
              WHERE SPID = '$key'";
    $result = mysqli_query($conn,$query);
    if($result === false){
      mysqli_rollback($conn);
      echo mysqli_error($conn);
      return -1;
    }      
  }

  //Insert the new ones
  foreach ($notInDB as $key => $value) {
    $name = $value['name'];
    $team = $value['team'];
    $role = $value['role'];
    $price = $value['price'];
    
    $query = "INSERT INTO soccer_player VALUES('$key','$name','$role','$team','$price')";
    $result = mysqli_query($conn,$query);
    if($result === false){
      mysqli_rollback($conn);
      echo mysqli_error($conn);
      return -1;
    }      
  }
  mysqli_commit($conn);
  echo "Market updated!";

  function myDump($var){
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
  }
?>