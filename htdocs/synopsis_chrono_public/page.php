<?php
require_once('../main.inc.php');
require_once('../synopsischrono/class/chrono.class.php');

ini_set('display_errors', 1);

$chronoRows = array();
$chronosList = array();
$id_chrono = 0;
$errorMsg = '';
$serial = '';
$userName = '';

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
        }
    }
    return $chronos;
}

if (isset($_GET['id_chrono']) && !empty($_GET['id_chrono'])) {
    $id_chrono = $_GET['id_chrono'];
} else if (isset($_POST['serial']) && isset($_POST['user_name'])) {
    $serial = $_POST['serial'];
    $userName = $_POST['user_name'];
    
    $chronos = getChronosBySerial($serial);
    if (!count($chronos)){
        $errorMsg = 'Aucun suivi SAV trouvé pour ce numéro de série';
    } else {
        foreach ($chronos as $chronoId) {
            $chrono = new Chrono($db);
            $chrono->fetch($chronoId);
            $chrono->getValuesPlus();
            $chrono->getValues();
            $chronosList[] = array(
                'chrono_id' => $chronoId,
                'ref' => ((isset($chrono->ref) && !empty($chrono->ref))?$chrono->ref:'inconnu'),
                'date_create' => (isset($dateCreate)?$dateCreate->format('d / m / Y'):'inconnue'),
                'symptom' => ((isset($chrono->Symptomes)))
            );
        }
    }
}

if ($id_chrono) {
    global $db;
    $chrono = new Chrono($db);
    $chrono->fetch((int) $id_chrono);
    $chronoRows = $chrono->getPublicValues();
    if (!count($chronoRows))
        $errorMsg = 'Numéro SAV absent ou invalide';
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
                    if (!empty($errorMsg)) {
                        echo '<div class="col-lg-9">';
                        echo '<p class="error">' . $errorMsg . '</p>';
                        echo '</div></div>';
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
                        echo '</tbody></table>';
                    } else if (count($chronosList) && isset($product)) {
                        echo '<p class="infos">Vous avez ' . count($chronosList) . ' suivis SAV enregistrés pour le matériel <strong>"' . $product['Nom'] . '"</strong></p>';
                        echo '<table><thead><tr>';
                        echo '<th colspan="2">Infos Matériel"</th>';
                        echo '</tr></thead><tbody>';
                        foreach ($product as $label => $value) {
                            echo '<tr><th>' . $label . '</th>';
                            echo '<td>' . $value . '</td></tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<div class="col-lg-8">';
                        echo '<form method="POST" action="./page.php" class="well">';
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