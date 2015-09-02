<?php
  include_once '../../vendor/phpQuery/phpQuery-onefile.php';
  require '../../vendor/autoload.php';
  use GuzzleHttp\Client;

  $dbhost = 'localhost'; //Name of the db host
  $dbname = 'fantacalcio'; //Name of the db
  $dbusername = 'root'; //Name of the db username
  $dbpsw = ''; //Password of the db
  $conn = mysqli_connect($dbhost, $dbusername, $dbpsw, $dbname) or die(mysqli_error());
  /*
    Deprecated for now
    getTransfermarktData($conn);
  */
  //getGazzettaData($conn);
  //getFootballData($conn);

/*
**   ====================================================================
**  |                        Transfermark Functions                      |
**   ====================================================================
*/

  function getTransfermarktData($conn){
    $squadPage = 'http://www.transfermarkt.it/jumplist/startseite/verein/';
    $serieATransferPage = 'http://www.transfermarkt.it/serie-a/transfers/wettbewerb/IT1';
    //Transfermarkt blocks these type of scraping, so we try to get around it
    ini_set("user_agent", "Descriptive user agent string");
    $htmlPage = file_get_contents($serieATransferPage);

    $doc = phpQuery::newDocument($htmlPage);
    $squadre = array();
    foreach(pq($doc['#verein_select_breadcrumb option']) as $data)
      if(!empty(pq($data)->attr('value'))){
        $name = pq($data)->text();
        $id = pq($data)->attr('value');
        $squadre[$name] = $id;
      }

    foreach ($squadre as $nome => $id) { //For each team
      $giocatoriSerieA = array();
      $htmlSquadPage = file_get_contents($squadPage.$id);
      $squadDoc = phpQuery::newDocument($htmlSquadPage);
      $i=0;
      foreach (pq($squadDoc['table.items span.hide-for-small a.spielprofil_tooltip']) as $playerName) { //For each player, save the name
        $giocatoriSerieA[$i]['playerID'] = pq($playerName)->attr('id');
        $giocatoriSerieA[$i]['playerName'] = pq($playerName)->text();
        $giocatoriSerieA[$i]['teamName'] = $nome;
        $i++;
      }
      $i=0;
      foreach (pq($squadDoc['td.zentriert.rueckennummer']) as $tdRole) { //For each player, save the role
        $giocatoriSerieA[$i]['role'] = pq($tdRole)->attr('title');
        $i++;
      }
      $i=0;
      foreach (pq($squadDoc['table.items td.rechts.hauptlink']) as $price) { //For each player, save its price
        $giocatoriSerieA[$i]['price'] = pq($price)->text();
        $i++;
      }
      for ($i=0; $i < count($giocatoriSerieA); $i++) {
        //Fix role (Porta -> POR, Centrale -> CEN etc..)
        switch ($giocatoriSerieA[$i]['role']) {
          case 'Porta':
            $giocatoriSerieA[$i]['role'] = "POR";
            break;
          case 'Difesa':
            $giocatoriSerieA[$i]['role'] = "DIF";
            break;
          case 'Centrocampo':
            $giocatoriSerieA[$i]['role'] = "CEN";
            break;
          case 'Attaccante':
            $giocatoriSerieA[$i]['role'] = "ATT";
            break;
          default:
            #Should never happen
            break;
        }
        //Fix price (i.e 9mln -> 9000000 or 50 mila -> 50000)
        $giocatoriSerieA[$i]['price'] = preg_replace_callback('/(\d+)(,)(\d+) (\bmln\b €)/', "priceFilterFunction", $giocatoriSerieA[$i]['price']);
        $giocatoriSerieA[$i]['price'] = preg_replace_callback('/(\d+) (\bmila\b) €/', "priceFilterFunction", $giocatoriSerieA[$i]['price']);
        $replacementString = array( 'À' => 'A',
                                    'à' => 'a', 
                                    'Á' => 'A',
                                    'á' => 'a',  
                                    'Â' => 'A',
                                    'â' => 'a',  
                                    'Ã' => 'A',
                                    'ã' => 'a',  
                                    'ä' => 'A',
                                    'Ä' => 'a',  
                                    'Å' => 'A',
                                    'å' => 'a',  
                                    'Æ' => 'AE',
                                    'æ' => 'ae',  
                                    'Ç' => 'C',
                                    'ç' => 'c',  
                                    'È' => 'E',
                                    'è' => 'e', 
                                    'É' => 'E',
                                    'é' => 'e',  
                                    'Ê' => 'E',
                                    'ê' => 'e',  
                                    'Ë' => 'E',
                                    'ë' => 'e',  
                                    'Ì' => 'I',
                                    'ì' => 'i',  
                                    'Í' => 'I',
                                    'í' => 'i',  
                                    'Î' => 'I',
                                    'î' => 'i',  
                                    'Ï' => 'I',
                                    'ï' => 'i',  
                                    'Ñ' => 'N',
                                    'ñ' => 'n',  
                                    'Ò' => 'O',
                                    'ò' => 'o',
                                    'Ó' => 'O',
                                    'ó' => 'o',
                                    'Ô' => 'O',
                                    'ô' => 'o', 
                                    'Õ' => 'O',
                                    'õ' => 'o', 
                                    'Ö' => 'O',
                                    'ö' => 'o', 
                                    'Ø' => 'O',
                                    'ø' => 'o', 
                                    'Ù' => 'U',
                                    'ù' => 'u', 
                                    'Ú' => 'U',
                                    'ú' => 'u', 
                                    'Û' => 'U',
                                    'û' => 'u', 
                                    'Ü' => 'U',
                                    'ü' => 'u', 
                                    'Ý' => 'Y',
                                    'ý' => 'y',
                                    'ß' => 'ss',
                                    'ÿ' => 'y',
                                    'Ÿ' => 'Y',   
                                    'Œ' => 'OE',
                                    'œ' => 'oe',  
                                    'Š' => 'S',
                                    'š' => 's');
        $giocatoriSerieA[$i]['playerName'] = strtr($giocatoriSerieA[$i]['playerName'],$replacementString);

        $pk = sanitizeInput($conn,$giocatoriSerieA[$i]['playerID']);
        $playerName = sanitizeInput($conn,$giocatoriSerieA[$i]['playerName']);
        $role = sanitizeInput($conn,$giocatoriSerieA[$i]['role']);
        $team = sanitizeInput($conn,$giocatoriSerieA[$i]['teamName']);
        $cost = (int)sanitizeInput($conn,$giocatoriSerieA[$i]['price']); 
        if($cost === 0) //If the player has no price, do not insert it in the db.
          continue;
        $query = "INSERT INTO soccer_player VALUES('$pk','$playerName','$role','$team','$cost')";
        $result = mysqli_query($conn,$query);
        if($result === false){
          echo mysqli_error($conn);
          return -1;
        }
      }
      unset($giocatoriSerieA);
    }
    echo "Transfers updated! <br /><br />";
  }

  //matches is array
  function priceFilterFunction($matches){
    if($matches[2] === 'mila')
      return $matches[1]."000";
    else{
      $result = $matches[1];
      $digits = preg_match_all('/[0-9]/', $matches[3]);
      $result .= $matches[3];
      for ($i=0; $i < 6-$digits; $i++) 
        $result .= "0";
      return (int)$result;      
    }
  }

/*
**   ========================================================================
**  |                           Gazzetta Functions                           |
**   ========================================================================
*/
  function getGazzettaData($conn){
    $statsPage = 'http://www.gazzetta.it/calcio/fantanews/statistiche/serie-a-2015-16/';

    ini_set("user_agent", "Descriptive user agent string");
    $htmlPage = file_get_contents($statsPage);
    $doc = phpQuery::newDocument($htmlPage);

    //Get Soccer Player Team, ID, Name, Role and Price 
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

      //Sanitize all input before DB insertion
      $SPID = sanitizeInput($conn,$player['SPID']);
      $name = sanitizeInput($conn,$player['name']);
      $role = sanitizeInput($conn,$player['role']);
      $team = sanitizeInput($conn,$player['team']);
      $price = (int)sanitizeInput($conn,$player['price']);
      //Insert into DB
      $query = "INSERT INTO soccer_player VALUES('$SPID','$name','$role','$team','$price')";
      $result = mysqli_query($conn,$query);
      if($result === false){
        echo mysqli_error($conn);
        return -1;
      }

    }
    echo "Transfers updated!<br />";
  }

/*
**   ========================================================================
**  |                        Football-Data Functions                         |
**   ========================================================================
*/
  function getFootballData($conn){
    //Get the number of teams from Serie A
    $serieA = getSoccerData('soccerseasons/401/');
    $teams = $serieA['numberOfTeams'];
    //Fetch the matchdays start and end. Add them to DB
    for ($i=1; $i <= ($teams*2)-2; $i++) {
      $matchday = getSoccerData('soccerseasons/401/fixtures?matchday='.$i);
      $start = -1;
      $end = -1;
      for ($j=0; $j < $matchday['count']; $j++) { 
        $date=new DateTime($matchday['fixtures'][$j]['date']);
        $date->setTimezone(new DateTimeZone('Europe/Rome'));
        if($start === -1 || $start >= $date->getTimestamp())
          $start = $date->getTimestamp();
        if($end === -1 || $end < ($date->getTimestamp()+(105*60))) //Last match + 105 minutes
          $end = $date->getTimestamp()+(105*60);
      }
      $query = "INSERT INTO match_day VALUES ('$i','$start','$end')";
      $result = mysqli_query($conn,$query);
      if($result === false){
        echo mysqli_error($conn);
        return -1;
      }      
    }
    echo "Dates added!","<br />";
  }
  
/*
**   ========================================================================
**  |                                  Other                                 |
**   ========================================================================
*/  
  function sanitizeInput($conn,$in){
    $in = strip_tags($in);    //Removes html tags (i.e <p>Text</p> becomes Text)
    $in = htmlentities($in);  //Converts html entities into their corresponding ASCII code (i.e < becomes &lt;)
    $in = stripslashes($in);  //Removes escaping chars like \
    return mysqli_real_escape_string($conn,$in);  //Escapes special characters like NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.  
  }

  function myDump($var){
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
  }

  function getSoccerData($link){
    $client = new Client();
    $key = '09b1c2be855b48f9a56ff81223f5be1f';

    $uri = 'http://api.football-data.org/alpha/'.$link;
    $header = array('headers' => array('X-Auth-Token' => $key));
    $response = $client->get($uri, $header);          
    return json_decode($response->getBody(),true);  
  }
?>