<?php
define("NOLOGIN", 1);
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

global $db;

if (isset($_POST['serial'])) {
    if (empty($_POST['serial'])) {
        $errors[] = 'Veuillez indiquer un numéro de série.';
    } else if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['serial'])) {
        $errors[] = 'Le numéro de série indiqué ne respecte pas le bon format.';
    } else {
        $serial = $_POST['serial'];
    }
} else if (isset($_GET['back_serial'])) {
    if (empty($_GET['back_serial'])) {
        $errors[] = 'Numéro de série absent.';
    } else if (!preg_match('/^[a-zA-Z0-9]+$/', $_GET['back_serial'])) {
        $errors[] = 'Une erreur est survenue. Numéro de série invalide.';
    } else {
        $serial = $_GET['back_serial'];
    }
}


if (isset($_GET['amp;user_name']))
    $_GET['user_name'] = $_GET['amp;user_name'];


if (isset($_GET['user_name']))
    $_POST['user_name'] = $_GET['user_name'];
    
if (isset($_POST['user_name'])) {
    if (empty($_POST['user_name'])) {
        $errors[] = 'Veuillez saisir les trois premières lettres de votre nom.';
    } else if (preg_match('/[<>]/', $_POST['user_name'])) {
        $errors[] = 'Caractères interdits : "<" et ">"';
    } else {
        $userName = strtolower(substr($_POST['user_name'], 0, 3));
    }
}
if (isset($_GET['id_chrono'])) {
    if (preg_match('/^[0-9]+$/', $_GET['id_chrono'])) {
        $id_chrono = (int) $_GET['id_chrono'];
    } else {
        $errors[] = 'ID SAV invalide.';
    }
}
if (isset($_GET['chronos'])) {
    $ids = explode('-', $_GET['chronos']);
    foreach ($ids as $id) {
        if (preg_match('/^[0-9]+$/', $id)) {
            $chronos[] = (int) $id;
        }
    }
} else if (isset($_GET['chronos_str'])) {
    if (preg_match('/^[0-9]+(\-[0-9]+)*$/', $_GET['chronos_str'])) {
        $chronoStr = $_GET['chronos_str'];
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
        }
    }
    return $chronos;
}

if ($serial && $userName) {
    if(is_numeric($serial) && strlen($serial) < 10)//C'est l'id
        $chronos = array($serial);
    else
    $chronos = getChronosBySerial($serial);
    if (!count($chronos)) {
        $errors[] = 'Aucun suivi SAV trouvé pour ce numéro de série.';
    } else if (count($chronos) == 1) {
        $chrono = new Chrono($db);
        $chrono->fetch($chronos[0]);
        $names = explode(' ', $chrono->societe->nom);
        foreach ($names as &$n) {
            $n = strtolower(substr(utf8_decode($n), 0, 3));
            if ($n === $userName) {
                $id_chrono = $chronos[0];
                break;
            }
        }
        if (!$id_chrono) {
            $msg = 'Un suivi SAV existe bien pour le numéro de série indiqué mais il semblerait que les 3 premières lettres indiquées pour votre nom ne correspondent pas.<br/>';
            $msg .= 'Veuillez saisir les 3 premières lettres du nom, du prénom ou du nom de société que vous avez indiqué lors de votre enregistrement auprès de nos services.';
            $errors[] = $msg;
        }
        $chronos = array();
    }
}

if (count($chronos)) {
    $first = true;
    foreach ($chronos as $idChrono) {

        $chrono = new Chrono($db);
        $chrono->fetch($idChrono);

        $check = true;
        if (isset($_POST['user_name'])) {
            $check = false;
            if ($userName) {
                $names = explode(' ', $chrono->societe->nom);
                foreach ($names as &$n) {
                    $n = strtolower(substr(utf8_decode($n), 0, 3));
                    if ($n === $userName) {
                        $check = true;
                        break;
                    }
                }
            }
        }
        if ($check) {
            if (count($chronos) > 1) {
                if (!$first) {
                    $chronoStr .= '-';
                } else
                    $first = false;
                $chronoStr .= $idChrono;
            }

            $chrono->getValues();
            $chronosList[] = array(
                'id_chrono' => $idChrono,
                'ref' => ((isset($chrono->ref) && !empty($chrono->ref)) ? $chrono->ref : 'inconnu'),
                'date_create' => ((isset($chrono->date) && !empty($chrono->date)) ? dol_print_date($chrono->date) : 'inconnue'),
                'symptom' => ((isset($chrono->values['Symptomes']) && !empty($chrono->values['Symptomes'])) ? $chrono->values['Symptomes'] : 'Non spécifié')
            );
        }
    }

    if ($userName && !count($chronosList)) {
        $msg = 'Il semblerait que les 3 premières lettres indiquées pour votre nom ne correspondent à aucun enregistrement.<br/>';
        $msg .= 'Veuillez saisir les 3 premières lettres du nom, du prénom ou du nom de société que vous avez indiqué lors de votre enregistrement auprès de nos services.';
        $errors[] = $msg;
    }
}

if ($id_chrono) {
    $chrono = new Chrono($db);
    $chrono->fetch((int) $id_chrono);
    $chronoRows = $chrono->getPublicValues();
    if (!count($chronoRows)) {
        $errors[] = 'Numéro SAV absent ou invalide.';
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
                    if(isset($chrono->societe) && is_object($chrono->societe))
                        echo "<h2>".$chrono->societe->getFullName($langs) . "</h2>";
                    echo "<h2>".$chrono->ref . "</h2>";
                    
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

                    if (count($chronosList)) {
                        echo '<div class="pull-right">';
                        echo '<a class="butAction" href="./' . $page . '"><i class="fa fa-search left"></i>Nouvelle recheche</a>';
                        echo '</div></div>';
                        echo '<div class="row">';

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
                            if (!empty($chronoStr) && $serial) {
                                echo '&chronos_str=' . $chronoStr . '&back_serial=' . $serial;
                            }
                            echo '"><i class="fa fa-bars left"></i>Afficher</a></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table></div>';
                        echo '<div class="row">';
                    } else if (count($chronoRows)) {
                        echo '<div class="pull-right">';
                        if ($chronoStr && $serial) {
                            echo '<a class="butAction" href="./' . $page . '?chronos=' . $chronoStr . '&back_serial=' . $serial . '">';
                            echo '<i class="fa fa-arrow-circle-left left"></i>Retour à la liste des suivis SAV</a>';
                        }
                        echo '<a class="butAction" href="./' . $page . '">';
                        echo '<i class="fa fa-search left"></i>Nouvelle recherche</a>';
                        echo '</div></div>';
                        echo '<div class="row">';
                        
                        if(isset($chrono->publicValues[1056]) && isset($chrono->publicValues[1056]['value'])){
                            $etat = $chrono->publicValues[1056]['value'];
                            $tabTextEtat = array("Nouveau" => "Nous allons bientot commencer le diagnostic de votre machine.",
                                "Examen en cours" => "Nous avons commencé le diagnostic de votre produit",
                                "Attente client" => "Nous attendons une information de votre part. Merci de nous contacter",
                                "Attente Pièce" => "Nous avons commandé une pièce ou un produit et nous l’attendons",
                                "Pièce reçu" => "Nous avons reçu la pièce commandée et elle est en cous de remontage",
                                "Terminé" => "Votre produit est terminé et à votre disposition",
                                "Fermé" => "Ce dossier est pour nous clôturé");
                            if(isset($tabTextEtat[$etat]))
                                echo "<h3>ETAT d'avancement : ".$tabTextEtat[$etat] . " </h3><br/><br/>";
                        }
                        
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
                        echo '<div class="col-lg-9">';
                        echo '<form method="POST" action="./' . $page . '" class="well">';
                        echo '<div class="form-group row">';
                        echo '<label class="col-lg-10 col-lg-offset-1" for="serial">Numéro de série du matériel: </label>';
                        echo '<input class="col-lg-10 col-lg-offset-1" id="serial" name="serial" type="text" value="' . $serial . '"/>';
                        echo '</div>';
                        echo '<div class="form-group row">';
                        echo '<label class="col-lg-10 col-lg-offset-1" for="user_name">Les trois premières lettres de votre nom: </label>';
                        echo '<input class="col-lg-10 col-lg-offset-1" id="user_name" name="user_name" type="text" value="' . $userName . '" max="3"/>';
                        echo '</div>';
                        echo '<div class="row">';
                        echo '<div class="col-lg-10 col-lg-offset-1">';
                        echo '<input type="submit" value="Rechercher" class="butAction pull-right"/>';
                        echo '</div>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                    }
                    ?>

            </div>
        </div>
    </body>
</html>