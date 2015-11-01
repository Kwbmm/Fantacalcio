<?php 
  $date = new DateTime("2015-11-01T18:00:00Z");
  $date->setTimezone(new DateTimeZone("Europe/Rome"));
  echo $date->getTimestamp(),"\n";
  echo $date->format("U");
?>