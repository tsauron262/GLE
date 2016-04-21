<?php
require_once('../main.inc.php');
require_once('../synopsischrono/class/chrono.class.php');

ini_set('display_errors', 1);

$rows = array();
$errorMsg = 'Numéro SAV absent ou invalide';
if (isset($_GET['id_sav']) && !empty($_GET['id_sav'])) {
    global $db;
    $chrono = new Chrono($db);
    $chrono->fetch((int) $_GET['id_sav']);

    $rows = $chrono->getPublicValues();
}
?>

<!DOCTYPE html>
<!--		author : graphic design and development by yo! - Copyright © 1998 - 2013 yo!.: www.yo.eu 		-->
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
                    if (count($rows)) {
                        echo '<table><thead></thead><tbody>';

                        foreach ($rows as $r) {
                            echo '<tr>';
                            echo '<th>' . $r['label'] . '</th>';
                            echo '<td>' . $r['value'] . '</td>';
                            echo '</tr>';
                        }

                        echo '</tbody></table>';
                    } else {
                        echo '<p class="error">'.$errorMsg.'</p>';
                    }
                    ?>

            </div>
        </div>
    </body>
</html>