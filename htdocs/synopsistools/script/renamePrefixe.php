<?php
/*                                                                            */
/* Titre          : modifier en masse le préfixe du nom de tables mysql       */
/*                                                                            */
/* Auteur         : forty                                                     */
/* Date édition   : 19 Sept 2008                                              */
/*                                                                            */
 
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
$sql_serveur = "freespackdbdd.mysql.db"; // Serveur mySQL 
$sql_base = "freespackdbdd"; // Base de données mySQL 
$sql_login = "freespackdbdd"; // Login de connection a mySQL 
$sql_password = "Freeparty9294"; // Mot de passe pour mySQL
 
$prefix_old = 'llx_';
$prefix_new = 'acs43_';

$lk = new mysqli($sql_serveur, $sql_login, $sql_password, $sql_base);

$q = $lk->query("SHOW TABLES LIKE '" . $prefix_old . "%'");
while ($r = $q->fetch_array(MYSQLI_NUM)){
    $new_name = $prefix_new . substr($r[0], strlen($prefix_old));
    $lk->query("RENAME TABLE `" . $r[0] . "`  TO `" . $new_name . "` ;") ;
}

 
//$lk = @mysql_connect($sql_serveur, $sql_login, $sql_password) or die(mysql_error
//());
//@mysql_select_db($sql_base, $lk) or die(mysql_error());
 
//$q = mysql_query("SHOW TABLES LIKE '" . $prefix_old . "%'", $lk) or die(
//mysql_error());
//while (($r = mysql_fetch_row($q)) !== false) {
//    $new_name = $prefix_new . substr($r[0], strlen($prefix_old));
//    mysql_query("RENAME TABLE `" . $r[0] . "`  TO `" . $new_name . "` ;", $lk) 
//or die(mysql_error());
//    echo $r[0] . ' => ' . $new_name . "<br>\n";
//}
?>