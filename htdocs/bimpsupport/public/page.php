<?php
define("NOLOGIN", 1);
require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";
BimpObject::getInstance('bimpsupport', 'BS_SAV');

ini_set('display_errors', 1);

$savRows = array();
$savsList = array();

$page = basename(__FILE__);
$errors = array();

$id_sav = 0;
$serial = '';
$userName = '';
$backSerial = '';
$savs = array();
$savStr = '';

global $db;





function getSavsBySerial($serial) {
    $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
    $rows = $equipment->getList(array(
        'serial' => $serial
    ));
    
    foreach ($rows as $row) {
        $sav = new BS_SAV($db);
        $rows2 = $sav->getList(array(
            'id_equipment' => $row['id']
        ));
        foreach ($rows2 as $row2) {
            $savs[] = $row2['id'];
        }
    }

    return $savs;
}

if (isset($_POST['serial'])) {
    if (empty($_POST['serial'])) {
        $errors[] = 'Veuillez indiquer un numéro de série.';
    } else if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['serial'])) {
        $errors[] = 'Le numéro de série indiqué ne respecte pas le bon format.';
    } else {
        $serial = $_POST['serial'];
    }
} else if (isset($_GET['serial'])) {
    if (empty($_GET['serial'])) {
        $errors[] = 'Numéro de série absent.';
    } else if (!preg_match('/^[a-zA-Z0-9]+$/', $_GET['serial'])) {
        $errors[] = 'Une erreur est survenue. Numéro de série invalide.';
    } else {
        $serial = $_GET['serial'];
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
if (isset($_GET['id_sav'])) {
    if (preg_match('/^[0-9]+$/', $_GET['id_sav'])) {
        $id_sav = (int) $_GET['id_sav'];
    } else {
        $errors[] = 'ID SAV invalide.';
    }
}
if (isset($_GET['savs'])) {
    $ids = explode('-', $_GET['savs']);
    foreach ($ids as $id) {
        if (preg_match('/^[0-9]+$/', $id)) {
            $savs[] = (int) $id;
        }
    }
} else if (isset($_GET['savs_str'])) {
    if (preg_match('/^[0-9]+(\-[0-9]+)*$/', $_GET['savs_str'])) {
        $savStr = $_GET['savs_str'];
    }
}


if ($serial && $userName) {
    if(is_numeric($serial) && strlen($serial) < 10)//C'est l'id
        $savs = array($serial);
    else
    $savs = getSavsBySerial($serial);
    if (!count($savs)) {
        $errors[] = 'Aucun suivi SAV trouvé pour ce numéro de série.';
    }/* else if (count($savs) == 1) {
        $sav = new BS_SAV($db);
        $sav->fetch($savs[0]);
        $names = explode(' ', $sav->societe->nom);
        foreach ($names as &$n) {
            $n = strtolower(substr(utf8_decode($n), 0, 3));
            if ($n === $userName) {
                $id_sav = $savs[0];
                break;
            }
        }
        if (!$id_sav) {
            die;
            $msg = 'Un suivi SAV existe bien pour le numéro de série indiqué mais il semblerait que les 3 premières lettres indiquées pour votre nom ne correspondent pas.<br/>';
            $msg .= 'Veuillez saisir les 3 premières lettres du nom, du prénom ou du nom de société que vous avez indiqué lors de votre enregistrement auprès de nos services.';
            $errors[] = $msg;
        }
        $savs = array();
    }*/
}

if (count($savs)) {
    $first = true;
    foreach ($savs as $idSav) {

        $sav = new BS_SAV($db);
        $sav->fetch($idSav);

        $check = false;
        if (isset($_POST['user_name'])) {
            $check = false;
            if ($userName) {
                $soc = $sav->getChildObject('client')->dol_object;
                $names = explode(' ', $soc->nom);
                foreach ($names as &$n) {
                    $n = strtolower(substr(utf8_decode($n), 0, 3));
                    if ($n === $userName) {
                        $check = true;
                        break;
                    }
                }
            }
        }
        else{
            $equipment = $sav->getChildObject('equipment');
            if($serial == $equipment->getData("serial"))
                $check = true;
        }
        if ($check) {
            if (count($savs) > 0) {
                if (!$first) {
                    $savStr .= '-';
                } else
                    $first = false;
                $savStr .= $idSav;
            }

            $savsList[] = array(
                'id_sav' => $idSav,
                'ref' => ((isset($sav->ref) && !empty($sav->ref)) ? $sav->ref : 'inconnu'),
                'date_create' => $sav->getData("date_create"),
                'symptom' => $sav->getData("symptomes")
            );
        }
    }

    if ($userName && !count($savsList)) {
        $msg = 'Il semblerait que les 3 premières lettres indiquées pour votre nom ne correspondent à aucun enregistrement.<br/>';
        $msg .= 'Veuillez saisir les 3 premières lettres du nom, du prénom ou du nom de société que vous avez indiqué lors de votre enregistrement auprès de nos services.';
        $errors[] = $msg;
    }
}

if ($id_sav) {
    $sav = new BS_SAV($db);
    $sav->fetch((int) $id_sav);
    $equipment = $sav->getChildObject('equipment');
    if($serial == $equipment->getData("serial")){
    
        $tech = $sav->getChildObject("user_tech");
        $savRows[] = array("label" => "Technicien",
                            "value" =>($tech->id > 0)? $tech->dol_object->getFullName(1) : "");
        $savRows[] = array("label" => "Symptômes",
                            "value" =>$sav->getData("symptomes"));
        $etat = $sav::$status_list[$sav->getData("status")]['label'];
        $savRows[] = array("label" => "Etat",
                            "value" =>$etat);
        $savRows[] = array("label" => "Diagnostic",
                            "value" =>$sav->getData("diagnostic"));
        $savRows[] = array("label" => "Résolution",
                            "value" =>$sav->getData("resolution"));
        $savRows[] = array("label" => "N° de dossier prestataire",
                            "value" =>$sav->getData("prestataire_number"));
        if ($sav->id < 1) {
            $errors[] = 'Numéro SAV absent ou invalide.';
        }
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
        <link rel="stylesheet" href="../../bimpcore/views/css/bimpcore_bootstrap.css">
        <link rel="stylesheet" href="../../theme/common/fontawesome/css/font-awesome.min.css?version=6.0.4">
        <link rel="stylesheet" href="./css/styles.css">
         <style>
            .error:before {
                position:initial;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row">
                    <?php
                    
                    /*Si erreur*/
                    if (count($errors)) {
                        echo '</div>';
                        echo '<div class="row">';
                        echo '<p class="error">';
                        foreach ($errors as $error) {
                            echo $error . '<br/>';
                        }
                        echo '</p>';
                        echo '</div>';
                        echo '<div class="row">';
                    }
                    
                    echo '<h1>Suivi SAV&nbsp;&nbsp;<i class="fa fa-hand-o-right"></i></h1>';

                    /* si liste des SAV*/
                    if (count($savRows)) {// si sav seul
                        if(isset($sav->societe) && is_object($sav->societe))
                            echo "<h2>".$sav->societe->getFullName($langs) . "</h2>";
                        echo "<h2>".$sav->ref . "</h2>";
                        echo '<div class="pull-right">';
                        if ($savStr && $serial) {
                            echo '<a class="butAction" href="./' . $page . '?savs=' . $savStr . '&serial=' . $serial . '">';
                            echo '<i class="fa fa-arrow-circle-left left"></i>Retour à la liste des suivis SAV</a>';
                        }
                        echo '<a class="butAction" href="./' . $page . '">';
                        echo '<i class="fa fa-search left"></i>Nouvelle recherche</a>';
                        echo '</div></div>';
                        echo '<div class="row">';
                        
                        if(isset($etat)){
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
                        foreach ($savRows as $r) {
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
                    else if (count($savsList)) {
                        echo '<div class="pull-right">';
                        echo '<a class="butAction" href="./' . $page . '"><i class="fa fa-search left"></i>Nouvelle recheche</a>';
                        echo '</div></div>';
                        echo '<div class="row">';

                        echo '<p class="infos">Vous avez ' . count($savsList) . ' suivis SAV enregistrés pour le n° de série <strong>"' . $serial . '"</strong></p>';
                        echo '<table><thead><tr>';
                        echo '<th>Référence</th>';
                        echo '<th>Date de création</th>';
                        echo '<th>Symptômes</th>';
                        echo '<th></th>';
                        echo '</tr></thead><tbody>';
                        foreach ($savsList as $savInfos) {
                            echo '<tr>';
                            echo '<td>' . $savInfos['ref'] . '</td>';
                            echo '<td>' . $savInfos['date_create'] . '</td>';
                            echo '<td>' . $savInfos['symptom'] . '</td>';
                            echo '<td><a class="butAction" href="./' . $page . '?id_sav=' . $savInfos['id_sav'];
                            if (!empty($savStr) && $serial) {
                                echo '&savs_str=' . $savStr . '&serial=' . $serial;
                            }
                            echo '"><i class="fa fa-bars left"></i>Afficher</a></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table></div>';
                        echo '<div class="row">';
                    }  

                    if (!count($savRows) && !count($savsList)) {
                        echo '</div>';
                        echo '<div class="row">';
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