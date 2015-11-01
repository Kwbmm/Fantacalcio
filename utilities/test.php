<?php 
  echo time(),"\n";
  echo date_default_timezone_get(),"\n";
  echo "Set timezone to rome", date_default_timezone_set("Europe/Rome"),"\n";
  echo date_default_timezone_get(),"\n";
  echo time(),"\n";
?>