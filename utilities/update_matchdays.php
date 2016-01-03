#!/usr/bin/php5.5-cli
<?php
  require_once '../vendor/phpQuery/phpQuery-onefile.php';
  require '../vendor/autoload.php';

//  use GuzzleHttp\Client;
//
  $dbhost = 'db591003352.db.1and1.com'; //Name of the db host
  $dbname = 'db591003352'; //Name of the db
  $dbusername = 'dbo591003352'; //Name of the db username
  $dbpsw = 'FArdzmrc90IO'; //Password of the db
  $conn = mysqli_connect($dbhost, $dbusername, $dbpsw, $dbname) or die(mysqli_error());

  mysqli_query($conn,"START TRANSACTION");
  //Update the matchdays from now untill the end
  $now = time();
  $query = "SELECT MID, start, end FROM match_day WHERE start > '$now' FOR UPDATE";
  $result = mysqli_query($conn,$query);
  if($result === false){
    mysqli_rollback($conn);
    echo mysqli_error($conn);
    return -1;
  }
  while(($row=mysqli_fetch_array($result,MYSQLI_ASSOC)) !== null){
    $mid = $row['MID'];
    // $matchday = getSoccerData('soccerseasons/401/fixtures?matchday='.$mid);
    $matchday = getLegaSerieA($mid);
    $start = -1;
    $end = -1;
    for ($j=0; $j < $matchday['count']; $j++) { 
      $date=date_create_from_format('d/m/Y G:i',$matchday['fixtures'][$j]['date'],new DateTimeZone('Europe/Rome'));
      // $date->setTimezone(new DateTimeZone('Europe/Rome'));
      if($start === -1 || $start >= $date->getTimestamp())
        $start = $date->getTimestamp();
      //Last match + 90 minutes + 15 minutes (pause)
      if($end === -1 || $end < ($date->getTimestamp()+(105*60)))
        $end = $date->getTimestamp()+(105*60);
    }
    $query = "UPDATE match_day SET start = '$start', end='$end'
              WHERE MID = '$mid'";
    $innserResult = mysqli_query($conn,$query);
    if($innserResult === false){
      mysqli_rollback($conn);
      echo mysqli_error($conn);
      return -1;
    }
  }
  mysqli_commit($conn);

  echo "Dates updated";

  function getSoccerData($link){
    $client = new Client();
    $key = '09b1c2be855b48f9a56ff81223f5be1f';
    $uri = 'http://api.football-data.org/alpha/'.$link;
    $header = array('headers' => array('X-Auth-Token' => $key));
    $response = $client->get($uri, $header);          
    return json_decode($response->getBody(),true);  
  }

  function getLegaSerieA($mid){
    $link = 'http://www.legaseriea.it/it/serie-a-tim/calendario-e-risultati/2015-16/UNICO/UNI/'.$mid;
    $matchday['fixtures'] = array();
    ini_set("user_agent", "Descriptive user agent string");
    $htmlPage = file_get_contents($link);
    $doc = phpQuery::newDocument($htmlPage);
    $matchday['count'] = pq($doc['section.risultati div.box-partita.col-xs-12.col-sm-4.col-md-3'])->size();
    foreach (pq($doc['section.risultati div.box-partita.col-xs-12.col-sm-4.col-md-3 div.datipartita p span']) as $date) {
      array_push($matchday['fixtures'], array('date'=>pq($date)->text()));
    }
    return $matchday;
  }


  function myDump($var){
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
  }
?>