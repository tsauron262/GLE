<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

//$dir = DOL_DATA_ROOT . '/bimpcore/apple_csv/2024/';
//
//foreach (scandir($dir) as $f) {
//    if (in_array($f, array('.', '..'))) {
//        continue;
//    }
//
//    echo '<br/>';
//    echo '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode('apple_csv/2024/' . $f) . '" target="_blank">' . $f . '</a>';
//}

$host = BimpCore::getConf('exports_ldlc_ftp_serv');
$port = 21;
$login = BimpCore::getConf('exports_ldlc_ftp_user');
$pword = BimpCore::getConf('exports_ldlc_ftp_mdp');

$ftp = ftp_connect($host, $port);

if ($ftp === false) {
    $errors[] = 'Echec de la connexion FTP avec le serveur "' . $host . '"';
} else {
    if (!ftp_login($ftp, $login, $pword)) {
        $errors[] = 'Echec de la connexion FTP - Identifiant ou mot de passe incorrect';
    } else {
        if (defined('FTP_SORTANT_MODE_PASSIF')) {
            ftp_pasv($ftp, true);
        } else {
            ftp_pasv($ftp, false);
        }

        $ftp_dir = '/' . BimpCore::getConf('exports_ldlc_ftp_dir') . '/statsapple/' . date('Y') . '/';
        $files = ftp_nlist($ftp, $ftp_dir);

        echo 'FICHIERS du dossier "' . $ftp_dir . '"';
        echo '<pre>';
        print_r($files);
        echo '</pre>';
    }

    ftp_close($ftp);
}

echo 'Erreurs : <pre>';
print_r($errors);
echo '</pre>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
