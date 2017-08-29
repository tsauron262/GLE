<?php
require_once '../main.inc.php';
require_once __DIR__ . '/BDS_Lib.php';

ini_set('display_errors', 1);

llxHeader();

print load_fiche_titre('Gestion des imports, exports et synchronisations des données', '', 'title_generic.png');

global $db;
$bdb = new BimpDb($db);

$processes = BDSProcess::getListData($bdb);
?>

<link type="text/css" rel="stylesheet" href="./views/css/styles.css"/>

<table id="processesList" class="noborder" width="100%">
   <tr class="liste_titre"><td colspan="6">Liste des processus</td></tr>
   <?php
   if (count($processes)) {
       foreach ($processes as $process) {
           echo '<tr>';
           echo '<td width="5%" style="text-align: center"><strong>' . $process['id'] . '</<strong></td>';
           echo '<td width="25%">';
           echo '<a href="' . DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $process['id'] . '">';
           echo $process['title'] . '</a></td>';
           echo '<td width="55%">' . $process['description'] . '</td>';
           echo '<td width="5%" style="text-align: center">' . BDSProcess::$types[$process['type']] . '</td>';
           echo '<td width="5%" style="text-align: center">';
           if ((int) $process['active']) {
               echo '<span class="success">activé</span>';
           } else {
               echo '<span class="danger">désactivé</span>';
           }
           echo '</td>';
           echo '<td width="5%">';
           echo '<a class="button" href="' . DOL_URL_ROOT . '/bimpdatasync/process.php?id_process=' . $process['id'] . '">Afficher</a>';
           echo '</td>';
           echo '</tr>';
       }
   } else {
       ?>

   <?php } ?>
</table>

<?php llxFooter(); ?>