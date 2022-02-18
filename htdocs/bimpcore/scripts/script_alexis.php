<?php
/*
 * 
 *  Script Alexis
 * 
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';


//$export = BimpCache::getBimpObjectInstance('bimptocegid', "BTC_exportRibAndMandat");
//
//$bdd = new BimpDb($db);
//
//$file = '4_'."BIMP".'_(RIBS)_' . "UNIQUE" . "_" . "Y2" . ".tra";
//
//$export_dir = PATH_TMP  ."/" . 'exportCegid' . '/' . $complementDirectory . '/';
//$export_project_dir = PATH_TMP . "/" . 'exportCegid' . '/';
//
////if(!file_exists($export_dir . $file)) {
//    $create_file = fopen($export_dir . $file, 'w');
//    fwrite($create_file, $export->head_tra());
////}
//$errors = array();
//
//$list = $bdd->getRows("societe_rib", "exported = 5");
//
//foreach($list as $rib) {
//    $ecriture .= $export->export_rib_exported($rib->rowid);
//}
//echo 'Fichier : '.$export_dir . $file;
//echo "<pre>";
//echo $ecriture;
//    fwrite($create_file, $ecriture);
//    fclose($create_file);
//
//print_r($errors);
//echo BimpTools::sendMailGrouper();

$limitRowid = 7265; // PROD: 7065

$contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
$table = 'contrat';
$where = 'rowid >= ' . $limitRowid . ' AND tmp_correct = 0';
$bimpDb = new BimpDb($db);

//$allContrat = $bimpDb->getRows($table, $where, 10);

$sql = 'SELECT rowid FROM llx_contrat WHERE rowid >= ' . $limitRowid . ' AND tmp_correct = 0  LIMIT 50';
$allContrat = $bimpDb->executeS($sql);

echo '<pre>';

$random_color_line = Array(rand(100, 200), rand(100, 200), rand(100, 200));

foreach($allContrat as $c) {
    $contrat->fetch($c->rowid);
    echo 'Date d\'effet: ' . $contrat->getData('date_start') . ' - Date de fin: '.$contrat->displayRealEndDate('Y-m-d').' ' . $contrat->getNomUrl() . '<br />';
    
    $children = $contrat->getChildrenList('lines');
    $parcour_renouvellement = 0;
    
    $dateStart = new DateTime($contrat->getData('date_start'));
    $dateEnd   = new DateTime($contrat->getData('date_start'));
    $dateEnd->add(new DateInterval('P' . $contrat->getData('duree_mois') . 'M'));
    $dateEnd->sub(new DateInterval('P1D'));
    
    $cache_date_start = $dateStart->format('Y-m-d');
    $cache_date_end   = $dateEnd->format('Y-m-d');
    $cache_color = $random_color_line;
    
    if(count($children) > 0) {
        foreach($children as $id_child) {
        
            $child = $contrat->getChildObject('lines', $id_child);
            if($child->getData('renouvellement') == $parcour_renouvellement + 1) {
                $cache_color = Array(rand(1, 100), rand(1, 100), rand(1, 100));

                $new_date_start = new DateTime($cache_date_end);
                $new_date_start->add(new DateInterval('P1D'));
                $cache_date_start = $new_date_start->format('Y-m-d');
                $new_date_end = new DateTime($cache_date_start);
                $new_date_end->add(new DateInterval('P' . $contrat->getData('duree_mois') . 'M'));
                $new_date_end->sub(new DateInterval('P1D'));
                $cache_date_end = $new_date_end->format('Y-m-d');
                $parcour_renouvellement++;
            }
            
            echo '<i style=\'margin-left:20px; color:rgb('.implode(',',$cache_color).')\'>'.$child->id.'('.$parcour_renouvellement.') Ouverture:' . $cache_date_start . ' Fermeture:'.$cache_date_end.'</i><br />';

        }
        
        if($cache_date_end === $contrat->displayRealEndDate('Y-m-d')) {
            echo '<b style=\'color:green;margin-left:20px\'>Ok pour changement</b><br />';
        } else {
            echo '<b style=\'color:darkred;margin-left:20px\'>Pas Ok pour changement</b><br />';
        }
        
    } else {
        echo '<b style=\'margin-left:20px;color:purple\'>Pas de lignes dans ce contrat</b>';
    }
    
    
    
    
    echo '<br />';
}
