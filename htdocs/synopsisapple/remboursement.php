<?php
//ALTER TABLE `llx_synopsis_apple_repair` 
//ADD `date_close` DATE NOT NULL DEFAULT '0000-00-00' AFTER `closed`, 
//ADD `is_reimbursed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `date_close`;

require_once('../main.inc.php');

require_once(DOL_DOCUMENT_ROOT.'/synopsischrono/class/chrono.class.php');
llxHeader();

?>

<link type="text/css" rel="stylesheet" href="rbt.css"/>

<table class="notopnoleftnoright" border="0" width="100%" style="margin-bottom: 2px;">
   <tbody>
      <tr>
         <td class="nobordernopadding hideonsmartphone" align="left" width="40" valign="middle">
            <img border="0" title="" alt="" src="/test2/theme/eldy/img/title_generic.png">
         </td>
         <td class="nobordernopadding" valign="middle">
            <div class="titre">Réparations non-remboursées</div>
         </td>
      </tr>
   </tbody>
</table>
<br/>

<div class="tabBar">
   <form method="POST" action="./remboursement.php" enctype="multipart/form-data">
      <label for="csvFile">Charger un nouveau fichier CSV: </label>
      <input type="file" name="csvFile" id="csvFile"/>
      <br/><br/>
      <label for="fileType">Type de fichier</label>
      <input type="radio" name="fileType" id="fileType_csv" value="fileType_csv" checked="checked"/>
      <label for="fileType_csv">Fichier texte CSV</label>
      <input type="radio" name="fileType" id="fileType_excel" value="fileType_excel"/>
      <label for="fileType_excel">Feuille excel</label>
      <br/><br/>
      <input type="submit" class="butAction" id="csvFilesubmit" name="csvFilesubmit" value="Envoyer"/>
   </form>
</div>

<div class="tabBar">
   <form method="POST" action="./remboursement.php">
      <label for="periodValue">Afficher les réparations fermées depuis plus de:</label>
      <input type="text" width="120px" value="<?php echo(isset($_POST['periodValue']) ? $_POST['periodValue'] : '1') ?>" name="periodValue" id="periodValue"/>
      <select id="periodUnit" name="periodUnit">
         <option value="day"<?php
         if (isset($_POST['periodUnit']) && ($_POST['periodUnit'] === 'day')) {
             echo ' selected';
         }
         ?>>Jour(s)</option>
         <option value="week"<?php
         if (isset($_POST['periodUnit']) && ($_POST['periodUnit'] === 'week')) {
             echo ' selected';
         }
         ?>>Semaine(s)</option>
         <option value="month"<?php
         if (isset($_POST['periodUnit']) && ($_POST['periodUnit'] === 'month')) {
             echo ' selected';
         } else if (!isset($_POST['periodUnit'])) {
             echo ' selected';
         }
         ?>>Mois</option>
      </select>
      <br/><br/>
      <input type="submit" class="butAction" id="periodSubmit" name="periodSubmit" value="Rechager la liste des réparations"/>
   </form>
</div>

<?php
global $periodUnits, $csvCols, $db;
$periodUnits = array('day', 'week', 'month');

$csvCols = array(
    'serialNumber' => array('col' => 1, 'label' => 'Numéro de série'),
    'imei' => array('col' => 2, 'label' => 'Numéro IMEI'),
    'warrantyCode' => array('col' => 5, 'label' => 'Code garantie'),
    'id' => array('col' => 8, 'label' => 'ID'),
    'repairType' => array('col' => 20, 'label' => 'Type de réparation')
);

function displayErrors($errors)
{
    if (!is_array($errors)) {
        $errors = array($errors);
    }

    echo '<div class="error">';
    if (count($errors) > 1) {
        echo count($errors) . ' erreurs détecteées:<br/>';
    } else {
        echo '1 erreur détectée: <br/>';
    }
    echo '<ol>';
    foreach ($errors as $e) {
        echo '<li>' . $e . '</li>';
    }
    echo '</ol>';
    echo '</div>';
}

function getRowsFromFile($fileName, $type = 'fileType_csv')
{
    global $csvCols;
    $rows = array();

    ini_set('display_errors', 1);

    switch ($type) {
        case 'fileType_csv':
            $file = file($fileName, FILE_IGNORE_NEW_LINES |  FILE_SKIP_EMPTY_LINES);
            foreach ($file as $line) {
                $datas = explode(';', $line);
                if (isset($datas[$csvCols['id']['col']]) && !empty($datas[$csvCols['id']['col']])) {
                    if (preg_match('/^G[0-9]{9}$/', $datas[$csvCols['id']['col']])) {
                        if (!array_key_exists($datas[$csvCols['id']['col']], $rows)) {
                            $row = array();
                            foreach ($csvCols as $key => $values) {
                                $row[$key] = $datas[$values['col']];
                            }
                            $rows[$datas[$csvCols['id']['col']]] = $row;
                        }
                    }
                }
            }
            break;

        case 'fileType_excel':
            require_once dirname(__FILE__) . '/phpExcel/PHPExcel.php';
            $file = PHPExcel_IOFactory::load($fileName);
            $sheet = $file->getSheet(0);
            $nRows = $sheet->getHighestRow();

            if ($nRows <= 0) {
                return $rows;
            } else {
                $rows = array();
                for ($i = 0; $i < $nRows; $i++) {
                    $id = $sheet->getCellByColumnAndRow($csvCols['id']['col'], $i)->getValue();
                    if (isset($id) && !empty($id)) {
                        if (preg_match('/^G[0-9]{9}$/', $id)) {
                            if (!array_key_exists($id, $rows)) {
                                $row = array();
                                foreach ($csvCols as $key => $values) {
                                    $row[$key] = $sheet->getCellByColumnAndRow($values['col'], $i)->getValue();
                                }
                                $rows[$id] = $row;
                            }
                        }
                    }
                }
            }
            break;
    }

    return $rows;
}

$errors = array();

$period = 'P1M';
$periodLabel = '1 mois';
if (isset($_POST['periodSubmit'])) {
    if (isset($_POST['periodValue']) && !empty($_POST['periodValue'])) {
        if (preg_match('/^[0-9]+$/', $_POST['periodValue'])) {
            $periodValue = (int) $_POST['periodValue'];
        } else {
            $errors[] = 'Veuillez indiquer une valeur numérique pour la période depuis laquelle les réparations à rechercher doivent être fermée';
        }
    } else {
        $errors[] = 'Veuillez indiquer une valeur numérique pour la période depuis laquelle les réparations à rechercher doivent être fermée';
    }

    if (!isset($_POST['periodUnit']) || empty($_POST['periodUnit'])) {
        $errors[] = 'Unité de la période de recherche absente';
    } else if (!in_array($_POST['periodUnit'], $periodUnits)) {
        $errors[] = 'Unité de la période de recherche invalide';
    }

    if (!count($errors)) {
        switch ($_POST['periodUnit']) {
            case 'day':
                $period = 'P' . $_POST['periodValue'] . 'D';
                $periodLabel = $_POST['periodValue'] . ' ' . (($_POST['periodValue'] > 1) ? 'jours' : 'jour');
                break;

            case 'week':
                $period = 'P' . $_POST['periodValue'] . 'W';
                $periodLabel = $_POST['periodValue'] . ' ' . (($_POST['periodValue'] > 1) ? 'semaines' : 'semaine');
                break;

            case 'month':
                $period = 'P' . $_POST['periodValue'] . 'M';
                $periodLabel = $_POST['periodValue'] . ' mois';
                break;
        }
    }
}

$csvRows = array();
if (isset($_POST['csvFilesubmit'])) {
    if (!isset($_POST['fileType']) || empty($_POST['fileType'])) {
        $errors[] = 'Type de fichier absent';
    } else if (!in_array($_POST['fileType'], array('fileType_csv', 'fileType_excel'))) {
        $errors[] = 'Type de fichier invalide';
    }

    if (!isset($_FILES) || !isset($_FILES['csvFile']) || (!$_FILES['csvFile']['name'])) {
        $errors[] = 'Aucun fichier sélectionné';
    } else {
        $infos = pathinfo($_FILES['csvFile']['name']);
        if (!in_array($infos['extension'], array('xlsx', 'xls', 'csv'))) {
            $errors[] = $this->l('Le fichier n\'a pas la bonne extension. Il doit obligatoirement avoir l\'extension "xls", "xlsx" ou "csv"');
        }

        if (!isset($_FILES['csvFile']['tmp_name']) || empty($_FILES['csvFile']['tmp_name']) ||
                !file_exists($_FILES['csvFile']['tmp_name'])) {
            $errors[] = 'Fichier absent';
        }

        if (!count($errors)) {
            $csvRows = getRowsFromFile($_FILES['csvFile']['tmp_name'], $_POST['fileType']);
            if (!count($csvRows)) {
                $errors[] = 'Le fichier ne contient aucune entrée valide';
            }
        }
    }
}

$repairs = array();

if (!count($errors)) {
    $datePeriodBegin = new DateTime();
    $datePeriodBegin->sub(new DateInterval($period));
    $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'synopsis_apple_repair ';
    $sql .= 'WHERE `closed` = 1 ';
    $sql .= ' AND `totalFromOrder` = 0 ';
    $sql .= ' AND `is_reimbursed` = 0';
    
    
    if(count($csvRows) > 0){
        $result = $db->query($sql);
        if ($result) {
            $updateSql = 'UPDATE ' . MAIN_DB_PREFIX . 'synopsis_apple_repair SET ';
            $updateSql .= '`is_reimbursed` = 1 ';
            $updateSql .= 'WHERE `rowid` = ';

            if ($db->num_rows($result)) {
                while ($obj = $db->fetch_object($result)) {
                    if (isset($obj->repairConfirmNumber) && !empty($obj->repairConfirmNumber)) {
                        if (array_key_exists($obj->repairConfirmNumber, $csvRows)) {
                            $db->query($updateSql . (int) $obj->rowid);
                        }
                    }
                }
            }
        }
    }
    
    
    
    $sql .= ' AND `date_close` < \'' . $datePeriodBegin->format('Y-m-d') . '\' ';
    $sql .= ' AND `date_close` > \'0000-00-00\' ';

    
    
//    echo $sql; exit;
    $result = $db->query($sql);

    if ($result) {

        if ($db->num_rows($result)) {
            while ($obj = $db->fetch_object($result)) {
                if (isset($obj->repairConfirmNumber) && !empty($obj->repairConfirmNumber)) {
                        $repairs[] = $obj;
                }
            }
        }
    } else {
        $errors[] = 'Echec du chargement des réparations non-remboursées. ' . $db->lasterror();
    }
}

if (count($errors)) {
    displayErrors($errors);
} else if (count($repairs)) {
    ?>
    <h3>Liste des réparations non-remboursées fermées depuis plus de <?php echo $periodLabel; ?></h3>
    <table class="noborder"">
       <thead>
          <tr class="liste_titre">
             <th class="liste_titre_sel" align="center" width="10%">ID</th>
             <th class="liste_titre" align="center" width="10%">ID SAV</th>
             <th class="liste_titre" align="center" width="30%">SAV</th>
             <th class="liste_titre" align="center" width="30%">Numéro de réparation</th>
             <th class="liste_titre" align="center" width="20%">date de fermeture</th>
          </tr>
       </thead>
       <tbody>
           <?php
           $pair = false;
           foreach ($repairs as $repair) {
               $chrono = new Chrono($db);
               $chrono->fetch($repair->chronoId);
               echo '<tr class="' . ($pair ? 'pair' : 'impair') . '">';
               echo '<td align="center">' . $repair->rowid . '</td>';
               echo '<td align="center">' . $repair->chronoId . '</td>';
               echo '<td align="left">' . $chrono->getNomUrl(1) . '</td>';
               echo '<td align="center">' . $repair->repairConfirmNumber . '</td>';
               if ($repair->date_close && ($repair->date_close !== '0000-00-00')) {
                   $dateClose = new DateTime($repair->date_close);
                   echo '<td align="center">' . $dateClose->format('d / m / Y') . '</td>';
                   unset($dateClose);
               } else {
                   echo '<td align="center">Non enregistrée</td>';
               }
               echo '</tr>';
               $pair = !$pair;
           }
           ?>
       </tbody>
    </table>
    <?php
} else {
    echo '<p>Aucune réparation non-remboursée trouvée.</p>';
}

llxFooter();