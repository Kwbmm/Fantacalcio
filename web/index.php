<?php 
  session_start();
  require_once __DIR__.'/../lib/common.php';
  require_once __DIR__.'/../lib/boot.php';
  require_once __DIR__.'/../lib/route.php';
  require_once __DIR__.'/../lib/errors.php';
  $app->run();
?>