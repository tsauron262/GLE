<?php

define("NOLOGIN", 1);  // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require_once('../../main.inc.php');
ini_set("display_errors", 1);

$error = array();
if(defined('CLOSE_FOR_PROXY'))
    $error[] = 'Fermé par le fichier de conf';

if(!count($error)){
    define('NO_SESSION_NETTOYAGE', true);
    $files = array(DOL_DATA_ROOT."/test_serveur.txt");

    foreach($files as $file){
        if(!file_get_contents($file)){
            $error[] = 'Pas de lecture '.$file;
            file_put_contents($file, 'ok');
        }
        $file = str_replace('.txt', '_eciture.txt', $file);
        if(!file_put_contents($file, 'ok'))
            $error[] = 'Pas d\'ecriture '.$file;

    }
    $files = array(PATH_TMP."/test_serveur.txt");
    foreach($files as $file){
        if(!file_get_contents($file)){
            $error[] = 'Pas de lecture '.$file;
            file_put_contents($file, 'ok');
        }
//        $file = str_replace('.txt', '_eciture.txt', $file);
        if(!file_put_contents($file, 'ok'))
            $error[] = 'Pas d\'ecriture '.$file;

    }
    $sqls = array("SELECT count(*) FROM ".MAIN_DB_PREFIX.'user'/*, 'CREATE TABLE IF NOT EXISTS `'.MAIN_DB_PREFIX.'test_serveur` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,`test` int(11) unsigned NOT NULL)', 'INSERT INTO '.MAIN_DB_PREFIX.'test_serveur (test) VALUES (4)'*/);
    foreach($sqls as $sql){
        if(!$db->query($sql))
            $error[] = 'Erreur SQL '.$sql;
    }
}

if(count($error)){
    header($_SERVER["SERVER_PROTOCOL"]." 503 Service Temporarily Unavailable", true, 503);
    dol_syslog('test serv err 503'.print_r($error,1),3);
    print_r($error);
}
else
    echo 'ok tout vas très très bien......';

