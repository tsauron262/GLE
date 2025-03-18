<?php

require_once '../../main.inc.php';
require_once '../../bimpcore/Bimp_Lib.php';
        
global $user;

if((int) $user->admin) {
echo 'debut';
    $warnings = array();

    $sql = 'SELECT COUNT(*) as nb, label, fk_product, value, fk_entrepot ';
    $sql .= 'FROM '.MAIN_DB_PREFIX.'stock_mouvement ';
    $sql .= 'WHERE bimp_origin = \'inventory2\' AND datem BETWEEN "2021-11-22" AND "2021-11-23" AND inventorycode = "inventory2-577"';
    $sql .= 'GROUP By label, fk_product, value, fk_entrepot ';
    $sql .= 'HAVING nb > 1 ORDER BY nb DESC';
    $rows = BimpObject::getBdb()->executeS($sql, 'array');

    if (is_array($rows)) {

        echo 'nb élément ' . sizeof($rows);
        foreach($rows as $r) {

            // Ignorer les équipement
            $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $r['fk_product']);
            if($prod->isSerialisable()) {
                echo $prod->getData('ref') . ' ignoré (équipement)<br/>';
                continue;
            }

            while(1 < (int) $r['nb']) { 
                echo "<br/>|".$r['label'].'| TTTTTT '. $r['fk_product'].' '. $r['value'].' '. $r['fk_entrepot'].'<br/>';
                revertMove($r['label'], $r['fk_product'], $r['value'], $r['fk_entrepot'], $warnings);
                --$r['nb'];
            }
        }
    }

} else {
    echo "Vous n'etes pas admin";
}
        
    
    
function revertMove($label, $fk_product, $value, $fk_entrepot, &$warnings) {

    $sql  = 'SELECT rowid ';
    $sql .= 'FROM '.MAIN_DB_PREFIX.'stock_mouvement ';
    $sql .= 'WHERE bimp_origin = \'inventory2\' ';
    $sql .= 'AND label="' . addslashes($label) . '" ';
    $sql .= 'AND fk_product="' . $fk_product . '" ';
    $sql .= 'AND value="' . $value . '" ';
    $sql .= 'AND fk_entrepot="' . $fk_entrepot . '" ';

    $rows = BimpObject::getBdb()->executeS($sql, 'array');

    if (is_array($rows)) {
        $id = (int) $rows[0]['rowid'];

        $bpm = BimpObject::getInstance('bimpcore', 'BimpProductMouvement', $id);
        $bpm->revertMouvement($warnings);

    }        
}

// Test si il y a des service dans les lignes de scan pour les inventaires ouvert
//SELECT d.id as id_scan_det, p.ref as p_ref, i.id as id_inv
//FROM '.MAIN_DB_PREFIX.'bl_inventory_det_2 as d
//LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON d.fk_product = p.rowid
//LEFT JOIN '.MAIN_DB_PREFIX.'bl_inventory_2 as i ON d.fk_inventory = i.id
//WHERE p.fk_product_type=1
//AND i.status=1
