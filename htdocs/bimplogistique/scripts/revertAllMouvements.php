<?php

require_once '../../main.inc.php';
require_once '../../bimpcore/Bimp_Lib.php';
        
global $user;

if((int) $user->admin) {
echo 'debut';
    $warnings = array();

    $sql = "SELECT DISTINCT a.rowid as id
FROM llx_stock_mouvement a
WHERE a.datem BETWEEN '2021-09-30' AND '2021-10-02' AND a.fk_user_author = '242' ";
    $rows = BimpObject::getBdb()->executeS($sql, 'array');

    if (is_array($rows)) {

        echo 'nb élément ' . sizeof($rows);
        foreach($rows as $r) {
            $bpm = BimpObject::getInstance('bimpcore', 'BimpProductMouvement', $r['id']);
            $bpm->revertMouvement($warnings);
        }
    }

} else {
    echo "Vous n'etes pas admin";
}
        
