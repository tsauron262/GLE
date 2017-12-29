<?php
require_once '../main.inc.php';
require_once __DIR__ . '/BDS_Lib.php';

llxHeader();

ini_set('display_errors', 1);

if (isset($_GET['deleteAllReports']) && $_GET['deleteAllReports']) {
    BDS_Report::deleteAll();
}
if (isset($_GET['deleteReport']) && $_GET['deleteReport']) {
    BDS_Report::deleteRef($_GET['deleteReport']);
}

$searchErrors = array();
$searchDateFrom = 0;
$searchDateTo = 0;

if (BDS_Tools::isSubmit('searchSubmit')) {
    $searchIdObject = BDS_Tools::getValue('searchIdObject');
    if (is_null($searchIdObject)) {
        $searchErrors[] = 'Veuillez indiquer un ID pour l\'objet à rechercher';
    } elseif (!preg_match('/^\d+$/', $searchIdObject)) {
        $searchErrors[] = 'L\'ID de l\'objet à rechercher doit être un nombre entier positif ' . $_POST['searchIdObject'];
    } else {
        $searchIdObject = (int) $searchIdObject;
    }
    if (BDS_Tools::isSubmit('searchDateFrom')) {
        $searchDateFrom = new DateTime(BDS_Tools::getDateTimeFromForm('searchDateFrom'));
    } else {
        $searchDateFrom = new DateTime(0);
    }
    if (BDS_Tools::isSubmit('searchDateTo')) {
        $searchDateTo = new DateTime(BDS_Tools::getDateTimeFromForm('searchDateTo'));
    } else {
        $searchDateTo = new DateTime();
    }

    if ($searchDateFrom->getTimestamp() > $searchDateTo->getTimestamp()) {
        $searchErrors[] = 'La date de début de la recherche doit être inférieure ou égale à la date de fin';
    }
}

print load_fiche_titre('Rapports des processus d\'import / export / synchronisation des données', '', 'title_generic.png');

$reports = BDS_Report::getReportsList();
$processes = BDSProcess::getProcessesQuery();

global $db;
$bdb = new BDSDb($db);
$form = new Form($db);

$dateTo = new DateTime();
$dateFrom = new DateTime();
$dateFrom->sub(new DateInterval('P1D'));
?>

<link type="text/css" rel="stylesheet" href="./views/css/styles.css"/>
<link type="text/css" rel="stylesheet" href="./views/css/reports.css"/>
<script type="text/javascript" src="./views/js/reports.js"></script>

<div class="fichecenter">
   <div class="fichehalfleft">
      <table  class="noborder" width="100%">
         <thead>
            <tr class="liste_titre">
               <td class="liste_titre">
                  Afficher un rapport
               </td>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td>
                  <form method="post" action="<?php DOL_URL_ROOT . '/bimpdatasync/rapport.php' ?>">
                     <div class="formRow">
                        <div class="formLabel">
                           Type de processus:
                        </div>
                        <div class="formInput">
                           <select class="fullwidth" id="processesToDisplay" name="processesToDisplay">
                              <option value="all">Tous les processus</option>
                              <?php
                              foreach ($processes as $process) {
                                  echo '<option value="' . $process['id'] . '">' . $process['name'] . '</option>';
                              }
                              ?>
                           </select>
                        </div>
                     </div>
                     <div class="formRow">
                        <div class="formLabel">
                           Type d'opération:
                        </div>
                        <div class="formInput">
                           <select class="fullwidth" id="typesToDisplay" name="typesToDisplay">
                              <option value="all">Tous les types d'opération</option>
                              <?php
                              foreach (BDS_Report::$OperationsTypes as $name => $opType) {
                                  echo '<option value="' . $name . '">' . $opType['name_plur'] . '</option>';
                              }
                              ?>
                           </select>
                        </div>
                     </div>
                     <div class="formRow">
                        <div class="formLabel">
                           Rapport à afficher:
                        </div>
                        <div class="formInput">
                           <select class="fullwidth" id="reportToLoad" name="reportToLoad">
                               <?php
                               foreach ($reports as $report) {
                                   echo '<option value="' . $report['ref'] . '"';
                                   echo ' data-id_process="' . $report['id_process'] . '"';
                                   echo ' data-nerrors="' . $report['nErrors'] . '"';
                                   echo ' data-nalerts="' . $report['nAlerts'] . '"';
                                   echo ' data-type="' . $report['type'] . '"';
                                   echo '>';
                                   echo $report['name'] . '</option>';
                               }
                               ?>
                           </select>
                        </div>
                     </div>
                     <div class="formSubmit">
                        <input class="butAction" type="submit" value="Charger ce rapport">
                     </div>
                  </form>
               </td>
            </tr>
         </tbody>
      </table>
   </div>
   <div class="fichehalfright">
      <div class="ficheaddleft">
         <table  class="noborder" width="100%">
            <thead>
               <tr class="liste_titre">
                  <td class="liste_titre">
                     Recherche
                  </td>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <td>
                     <form method="post" action="<?php DOL_URL_ROOT . '/bimpdatasync/rapport.php' ?>">
                        <div class="formRow">
                           <div class="formLabel">
                              Type d'objet:
                           </div>
                           <div class="formInput">
                              <select class="fullwidth" id="searchObject" name="searchObject">
                                  <?php
                                  foreach (BDS_Report::$objectsLabels as $object => $label) {
                                      echo '<option value="' . $object . '">' . ucfirst(BDS_Report::getObjectLabel($object)) . '</option>';
                                  }
                                  ?>
                              </select>
                           </div>
                        </div>
                        <div class="formRow">
                           <div class="formLabel">
                              ID de l'objet:
                           </div>
                           <div class="formInput">
                              <input type="text" value="" id="searchIdObject" name="searchIdObject"/>
                           </div>
                        </div>
                        <div class="formRow">
                           <div class="formLabel">
                              Du:
                           </div>
                           <div class="formInput">
                               <?php $form->select_date($dateFrom->getTimestamp(), 'searchDateFrom', 1, 1) ?>
                           </div>
                        </div>
                        <div class="formRow">
                           <div class="formLabel">
                              Au:
                           </div>
                           <div class="formInput">
                               <?php $form->select_date($dateTo->getTimestamp(), 'searchDateTo', 1, 1) ?>
                           </div>
                        </div>
                        <?php
                        if (isset($searchErrors) && count($searchErrors)) {
                            echo '<div class="alert form-alert alert-danger">';
                            echo '<ul>';
                            foreach ($searchErrors as $e) {
                                echo '<li>' . $e . '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }
                        ?>
                        <div class="formSubmit">
                           <input class="butAction" type="submit" name="searchSubmit" value="Rechercher">
                        </div>
                     </form>
                  </td>
               </tr>
            </tbody>
         </table>
      </div>
   </div>
</div>

<div class="fichecenter toolBar">
   <a class="butAction" href="<?php echo DOL_URL_ROOT . '/bimpdatasync/process.php' ?>">
      Liste des processus
   </a>
   <a class="butActionDelete delete-button" href="<?php echo DOL_URL_ROOT . '/bimpdatasync/rapports.php?deleteAllReports=1' ?>">
      Supprimer tous les rapports
   </a>
</div>
<?php
if (BDS_Tools::isSubmit('searchSubmit')) {
    if (!count($searchErrors)) {
        if (!function_exists('renderObjectNotifications')) {
            require_once __DIR__ . '/views/render.php';
        }
        $data = BDS_Report::getObjectNotifications($_POST['searchObject'], (int) $_POST['searchIdObject'], $searchDateFrom->format('Ymd-His'), $searchDateTo->format('Ymd-His'));
        $title = 'Résultats de recherche pour "' . BDS_Tools::makeObjectName($bdb, $_POST['searchObject'], (int) $_POST['searchIdObject']) . '"';
        $title .= ' du ' . $_POST['searchDateFrom'] . ' au ' . $_POST['searchDateTo'];
        echo renderObjectNotifications($data, $title);
    }
} else {
    $report_ref = BDS_Tools::getValue('reportToLoad', null);
    if (!is_null($report_ref)) {
        echo '<div class="fichecenter">';
        if (file_exists(DOL_DATA_ROOT . '/bimpdatasync/reports/' . $report_ref . '.csv')) {
            $report = new BDS_Report(null, null, $report_ref);

            if (!function_exists('renderReportContent')) {
                require_once __DIR__ . '/views/render.php';
            }

            echo renderReportContent($report);
        } else {
            echo '<p class="error">';
            echo 'Le fichier correspondant à ce rapport semble ne pas exister';
            echo '</p>';
        }

        echo '</div>';
    }
}
?>


<?php
llxFooter();
?>