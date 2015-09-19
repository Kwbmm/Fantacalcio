#!/usr/bin/php5.5-cli
<?php 
  if($argc !== 2){
    echo "Syntax: $argv[0] <number>\n";
    return -1;
  }

  $iterations = (int)$argv[1];
  for ($i=0; $i < $iterations; $i++) { 
    echo $i+1,": ", getRandomString(12,false), "\n";
  }
  return 0;

  function getRandomString($length = 12,$specials=true) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if($specials)
      $characters.='|!£$%&/()=?^-_<>';
    $string = '';

    for($i = 0; $i < $length; $i++)
      $string .= $characters[mt_rand(0, strlen($characters) - 1)];

    return $string;
  }
?>