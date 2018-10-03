<?php

$date = new DateTime('2016-09-27T10:03:45.777857Z', new DateTimeZone("GMT"));


echo 'Avant : '.$date->format('H:i:s').'<br/>';
//$date->setTimezone(new DateTimeZone("GMT"));
//echo 'Après: '.$date->format('H:i:s').'<br/>';
$date->setTimezone(new DateTimeZone("Europe/Paris"));
echo 'Après2: '.$date->format('H:i:s').'<br/>';
