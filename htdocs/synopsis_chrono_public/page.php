<?php
require_once('../main.inc.php');
require_once('../synopsischrono/class/chrono.class.php');

ini_set('display_errors', 1);

$chronoRows = array();
$chronosList = array();

$page = basename(__FILE__);
$errors = array();

$id_chrono = 0;
$serial = '';
$userName = '';
$backSerial = '';
$chronos = array();
$chronoStr = '';

if (isset($_POST['serial'])) {
    if (preg_match('/^[a-zA-Z0-9]$/', $_POST['serial'])) {
        $serial = $_POST['serial'];
    } else {
        $errors[] = 'Le numéro de série indiqué ne respecte pas le bon format.';
    }
}
if (isset($_POST['user_name'])) {
    if (preg_match('/^[a-zA-Z \-]$/', $_POST['user_name'])) {
        $userName = $_POST['user_name'];
    } else {
        $errors[] = 'Veuillez ne saisir que des lettre, un espace ou un "-" pour les trois permières lettres de votre nom.';
    }
}
if (isset($_GET['id_chrono'])) {
    if (preg_match('/^[0-9]$/', $_GET['id_chrono'])) {
        $id_chrono = (int) $_GET['id_chrono'];
    } else {
        $errors[] = 'ID SAV invalide';
    }
}
if (isset($_GET['chronos'])) {
    $ids = explode('-', $_GET['chronos']);
    foreach ($ids as $id) {
        if (preg_match('/^[0-9]$/', $id)) {
            $chrono[] = (int) $id;
        }
    }
}

function getChronosBySerial($serial) {
    global $db;
    $sql = 'SELECT ee.fk_source as id_chrono FROM ' . MAIN_DB_PREFIX . 'element_element ee ';
    $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'synopsischrono_chrono_101 p ON p.id = ee.fk_target ';
    $sql .= 'WHERE ee.targetType = \'productCli\' AND ee.sourcetype = \'SAV\' AND p.N__Serie = \'' . $serial . '\'';

    $result = $db->query($sql);
    $chronos = array();

    if ($db->num_rows($result) > 0) {
        while ($r = $db->fetch_object($result)) {
            $chronos[] = $r->id_chrono;
            $chronos[] = $r->id_chrono;
        }
    }
    return $chronos;
}

if ($serial && $userName) {
    $chronos = getChronosBySerial($serial);
    if (!count($chronos)) {
        $errors[] = 'Aucun suivi SAV trouvé pour ce numéro de série';
    } else if (count($chronos) == 1) {
        $id_chrono = $chronos[0];
        $chronos = array();
    }
}

if (count($chronos)) {
    $first = true;
    foreach ($chronos as $idChrono) {
        if (!$first) {
            $chronoStr .= '-';
        } else
            $chronoStr = false;
        $chronoStr .= $idChrono;
        
        $chrono = new Chrono($db);
        $chrono->fetch($idChrono);
        $chrono->getValuesPlus();
        $chrono->getValues();
        $chronosList[] = array(
            'id_chrono' => $idChrono,
            'ref' => ((isset($chrono->ref) && !empty($chrono->ref)) ? $chrono->ref : 'inconnu'),
            'date_create' => ((isset($chrono->date) && !empty($chrono->date)) ? dol_print_date($chrono->date) : 'inconnue'),
            'symptom' => ((isset($chrono->values['Symptomes']) && !empty($chrono->values['Symptomes'])) ? $chrono->values['Symptomes'] : 'Non spécifié')
        );
    }
}

if ($id_chrono) {
    global $db;
    $chrono = new Chrono($db);
    $chrono->fetch((int) $id_chrono);
    $chronoRows = $chrono->getPublicValues();
    if (!count($chronoRows)) {
        $errors[] = 'Numéro SAV absent ou invalide';
    }
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="fr-FR">
    <head>
        <meta name="keywords" content="revendeur apple, apple, agréé apple, boutique apple, magasin apple, dépannage ordinateur, récupération de données, formation, formations, réseau, ipad, imac, mac, ipod, iphone, apple part dieu, informatique part dieu, apple lyon,, apple saint etienne, apple saint péray, apple valence">
        <meta name="description" content="APPLE PREMIUM RESELLER - Revendeur Apple LYON, VALENCE, SAINT-ETIENNE, MONTBELIARD, BESANÇON, Boutique Apple ayant le sens du conseil, s'occupant de votre installation, maintenance, suivi et formation">
        <meta charset="UTF-8" />
        <meta name="viewport" content="initial-scale=1.0, width=device-width, target-densitydpi=device-dpi">
        <title>SAV, Support &amp; Hotline / Bimp Informatique</title>
        <link rel="stylesheet" href="./tools/bootstrap.min.css">
        <link rel="stylesheet" href="./tools/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="./css/styles.css">
    </head>
    <body>
        <div class="container">
            <div class="row">
                <h1>Suivi SAV&nbsp;&nbsp;<i class="fa fa-hand-o-right"></i></h1>
                    <?php
                    if (count($errors)) {
                        echo '<div class="col-lg-9">';
                        echo '<p class="error">';
                        foreach ($errors as $error) {
                            echo $error . '<br/>';
                        }
                        echo '</p>';
                        echo '</div></div>';
                        echo '<div class="row">';
                    }

                    if (isset($backSerial)) {
                        echo '<div class="pull-right">';
                        echo '<a class="butAction" href="./' . $page . '?serial=' . $backSerial . '><i class="fa fa-arrow-circle-left"></i>&nbsp;&nbsp;Retour à la liste des suivis SAV</a>';
                        echo '</div></div>';
                        echo '<div class="row">';
                    }

                    if (count($chronosList)) {
                        echo '<p class="infos">Vous avez ' . count($chronosList) . ' suivis SAV enregistrés pour le n° de série <strong>"' . $serial . '"</strong></p>';
                        echo '<table><thead><tr>';
                        echo '<th>Référence</th>';
                        echo '<th>Date de création</th>';
                        echo '<th>Symptomes</th>';
                        echo '<th></th>';
                        echo '</tr></thead><tbody>';
                        foreach ($chronosList as $chronoInfos) {
                            echo '<tr>';
                            echo '<td>' . $chronoInfos['ref'] . '</td>';
                            echo '<td>' . $chronoInfos['date_create'] . '</td>';
                            echo '<td>' . $chronoInfos['symptom'] . '</td>';
                            echo '<td><a class="butAction" href="./' . $page . '?id_chrono=' . $chronoInfos['id_chrono'];
                            if (!empty($serial)) {
                                echo '&backchronos=' . $serial;
                            }
                            echo '">Afficher</a></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table></div>';
                        echo '<div class="row">';
                    }

                    if (count($chronoRows)) {
                        echo '<table><thead></thead><tbody>';
                        $firstLoop = true;
                        foreach ($chronoRows as $r) {
                            if ($firstLoop) {
                                echo '<thead><tr>';
                                echo '<th>' . $r['label'] . '</th>';
                                echo '<th>' . $r['value'] . '</th>';
                                echo '</tr></thead><tbody>';
                                $firstLoop = false;
                            } else {
                                echo '<tr>';
                                echo '<th>' . $r['label'] . '</th>';
                                echo '<td>' . $r['value'] . '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</tbody></table></div>';
                        echo '<div class="row">';
                    }

                    if (!count($chronoRows) && !count($chronosList)) {
                        echo '<div class="col-lg-8">';
                        echo '<form method="POST" action="./' . $page . '" class="well">';
                        echo '<div class="form-group row">';
                        echo '<label class="col-lg-5" for="serial">Numéro de série du matériel: </label>';
                        echo '<input class="col-lg-8" id="serial" name="serial" type="text" value="' . $serial . '"/>';
                        echo '</div>';
                        echo '<div class="form-group row">';
                        echo '<label class="col-lg-5" for="user_name">Les trois premières lettres de votre nom: </label>';
                        echo '<input class="col-lg-8" id="user_name" name="user_name" type="text" value="' . $userName . '" max="3"/>';
                        echo '</div>';
                        echo '<div class="row">';
                        echo '<input type="submit" value="Rechercher" class="butAction pull-right"/>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                    }
                    ?>

            </div>
        </div>
    </body>
</html>