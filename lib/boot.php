<?php 
  require_once __DIR__.'/../vendor/autoload.php';
  $app = new Silex\Application();
  
  $app['debug'] = true;

  //Save some init data inside $app;
  $app['apiKey'] = '09b1c2be855b48f9a56ff81223f5be1f';
  $app['siteName'] = 'Fantacalcio';
  
  if($_SERVER['SERVER_NAME'] !== 'localhost'){
    $app['dbhost'] = 'db591003352.db.1and1.com'; //Name of the db host
    $app['dbname'] = 'db591003352'; //Name of the db
    $app['dbusername'] = 'dbo591003352'; //Name of the db username
    $app['dbpsw'] = 'FArdzmrc90IO'; //Password of the db
  }
  else{
    $app['dbhost'] = 'localhost'; //Name of the db host
    $app['dbname'] = 'fantacalcio'; //Name of the db
    $app['dbusername'] = 'root'; //Name of the db username
    $app['dbpsw'] = ''; //Password of the db
  }

  $app['conn'] = mysqli_connect($app['dbhost'], $app['dbusername'], $app['dbpsw'], $app['dbname']) or die(mysqli_error());
  
  $app->register(new Silex\Provider\TwigServiceProvider(), array('twig.path' => __DIR__.'/../templates'));
  $app->register(new SilexGuzzle\GuzzleServiceProvider());
  
  /*
  **  config.json maybe will be used in the future for storing other start data
  **  which do not fit inside the DB.
  */
  $app['configFilePath'] = __DIR__.'/config.json';
  if(file_exists($app['configFilePath'])){
    updateConfig($app);
  }
  else{
    generateConfig($app);
  } //File doesn't exits, create it
    
?>