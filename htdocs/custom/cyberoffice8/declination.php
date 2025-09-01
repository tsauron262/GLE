<?php
/* 
 * @author 	LVSinformatique <contact@lvsinformatique.com>
 * @copyright  	2021 LVSInformatique
 * This source file is subject to a commercial license from LVSInformatique
 * Use, copy or distribution of this source file without written
 * license agreement from LVSInformatique is strictly forbidden.
 */
define('NOCSRFCHECK', 1);
require_once '../../main.inc.php';

$dataP = GETPOST('data');
$dataD = GETPOST('data1');
$dataS = GETPOST('data2', 'alpha');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
        ." WHERE shop ='".$dataS."' AND attribut=".$dataP;
//print $sql;
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    $obj = $db->fetch_object($resql);
    if ($dataD == -1) {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_cyberoffice3 WHERE rowid=".$obj->rowid;
    } else {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
        ." WHERE shop ='".$dataS."' AND variant=".$dataD;
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0)
            print 2;
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_cyberoffice3 SET variant=".$dataD." WHERE rowid=".$obj->rowid;
    }
    $resqlU = $db->query($sql);
} else {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cyberoffice3"
        ." WHERE shop ='".$dataS."' AND variant=".$dataD;
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0)
        print 2;
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_cyberoffice3 (attribut, variant, shop)"
            ." VALUES (".$dataP.",".$dataD.",'".$dataS."')";
    //print $sql;
    $resqlI = $db->query($sql);
}

return 1;
