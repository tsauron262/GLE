<?php
/*
 * 
 *  Script Alexis
 * 
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';

llxHeader();

$limitRowid = 7065; // PROD: 7065

$contrat = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
$table = 'contrat';
$where = 'rowid >= ' . $limitRowid . ' AND tmp_correct = 0';
$bimpDb = new BimpDb($db);

//$allContrat = $bimpDb->getRows($table, $where, 10);

$sql = 'SELECT rowid FROM llx_contrat WHERE rowid >= ' . $limitRowid . ' AND tmp_correct = 0  LIMIT 1000';
$allContrat = $bimpDb->executeS($sql);

echo '<pre>';

$random_color_line = Array(rand(100, 200), rand(100, 200), rand(100, 200));
$log = '';

foreach($allContrat as $c) {
    $contrat->fetch($c->rowid);
    $errors = Array();
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

    $log .= 'Contrat Numéro ' . $contrat->getRef() . "\n";

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

            $oldOpen = new DateTime($child->getData('date_ouverture_prevue'));

            if($child->getData('date_fin_validite')) {
                $oldClose = new DateTime($child->getData('date_fin_validite'));
                $oldCloseDisplay = $oldClose->format('Y-m-d');
            } else {
                $oldCloseDisplay = 'Il n\'y à pas de date de fin';
            }

            $changementLineDisplay = '';

            if($oldOpen->format('Y-m-d') == $cache_date_start && $oldCloseDisplay == $cache_date_end) {
                $changementLineDisplay = '<b style=\'color:green\'>Pas de changement à opérer</b>';
            } else {
                $changementLineDisplay = '<b style=\'color:darkred\'>Des changements sont à opérer</b>';
                //$errors = $child->updateField('date_ouverture_prevue', $cache_date_start);
                //BimpTools::merge_array($errors, $child->updateField('date_fin_validite', $cache_date_end));
                
                if(!count($errors)) {
                    $log .= '-> Ligne #' . $child->id . ' => Changement (OLD OPEN: '.$oldOpen->format('Y-m-d').' OLD CLOSE: '.$oldCloseDisplay.')' . "\n"; 
                    $log .= '->-> Ligne #' . $child->id . ' => NEW OPEN: ' . $cache_date_start . ' NEW CLOSE: ' . $cache_date_end . "\n";
                } else {
                    $log .= '-> Ligne #' . $child->id . ' => Erreur lors de la modification de la ligne ' . "\n" . print_r($errors, 1) . "\n"; 
                }
                
            }

            echo '<i style=\'margin-left:20px; color:rgb('.implode(',',$cache_color).')\'>'.$child->id.'('.$parcour_renouvellement.') Ouverture:' . $cache_date_start . ' Fermeture:'.$cache_date_end.'</i> - <b>(OLD OPEN: '.$oldOpen->format('Y-m-d').' OLD CLOSE: '.$oldCloseDisplay.')</b> '.$changementLineDisplay.'<br />';

        }

        if($cache_date_end === $contrat->displayRealEndDate('Y-m-d')) {
            echo '<b style=\'color:green;margin-left:20px\'>Les dates calculées correspondent aux dates du contrat</b><br />';
        } else {
            echo '<b style=\'color:darkred;margin-left:20px\'>Les dates calculées ne correspondent pas aux dates du contrat</b><br />';
        }

    } else {
        echo '<b style=\'margin-left:20px;color:purple\'>Pas de lignes dans ce contrat</b>';
    }




    echo '<br />';
}

$log .= "\n\n";
//$logs_file = fopen(PATH_TMP . '/dolibarr_correcte_dates_lines_contrats.log', 'a+');
//fwrite($logs_file, $log);
//fclose($logs_file);

llxFooter();