<?php

$domaine = str_replace("www.", "", $_SERVER['HTTP_HOST']);
$file = str_replace("conf.php", "conf-" . $domaine . ".php", $conffile);
if (is_file($file))
    $chem = "";
elseif(is_file("../".$file))
    $chem = "../";
elseif(is_file("../../".$file))
    $chem = "../../";
   
if(isset($chem)){
    $conffile = $chem.str_replace("conf.php", "conf-" . $domaine . ".php", $conffile);
    $conffiletoshow = $chem.str_replace("conf.php", "conf-" . $domaine . ".php", $conffiletoshow);
    $conffiletoshowshort = $chem.str_replace("conf.php", "conf-" . $domaine . ".php", $conffiletoshowshort);
}