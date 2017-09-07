<?php

require_once '../master.inc.php';
ini_set('display_errors', 1);
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/BDS_Lib.php';

ini_set('display_errors', 1);

function testReq()
{
    global $conf, $db;

    BDS_Process::$debug_mod = 1;
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    $user = new User($db);
    $user->fetch(1);

    $error = 0;
    $process = BDS_Process::createProcessByName($user, 'PS_Sync', $error);
    if ($error) {
        echo 'Erreur: ' . $error . '<br/>';
    }

    if (!is_null($process)) {
        $errors = array();
        
        $process->test();

        if (count($errors)) {
            echo 'Erreurs: <pre>';
            print_r($errors);
            echo '</pre>';
        }
    }
}

testReq();

function testImg()
{
    global $conf, $db;

    ini_set('display_errors', 1);

    if (!class_exists('Product')) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    }

    $product = new Product($db);
    $product->fetch(6782);
    $dir = rawurlencode(get_exdir($product->id, 2, false, false, $product, 'product') . $product->id . '/photos/');
    echo rawurlencode($dir);
}

//testImg();

function installProcess()
{
    BDS_Process::$debug_mod = true;
    include_once __DIR__ . '/classes/process_overrides/BDS_PS_SyncProcess.php';
    BDS_PS_SyncProcess::install();
}

//installProcess();

function testSQL()
{
    global $db;
    $bdb = new BimpDb($db);

    $row = $bdb->getRow('bds_object_sync_data', '`id` = 427');
    echo '<pre>';
    print_r($row);
    exit;
}

//testSQL();

function testMatch()
{
    echo 'TEST <br/>';
//    $match = new BDSProcessMatchingValues();
//    $match->fetch(2);
//
//    $res = $match->getMatchedValue(6);
//    if (is_null($res)) {
//        echo $match->db->db->error();
//    }
//    echo 'Res: ' . $res;

    $match = BDSProcessMatchingValues::createInstanceByName(1, 'order_state');
    if (is_null($match)) {
        echo 'PAS OK <br/>';
    } else {
        echo 'ID: ' . $match->id;
    }
}

//testMatch();

function testFtp()
{
    $ftp = ftp_connect('ExportFTP.techdata.fr');

    if ($ftp) {
        echo 'Connexion OK<br/>';
        $logged = ftp_login($ftp, 'allcom', 'Jrl$98Ztj*DH');

        if ($logged) {
            echo 'Login OK<br/>';

            $files = ftp_nlist($ftp, '/');
            if (!$files) {
                echo 'Echec de la récup des fichiers';
            } else {
                echo 'Fichiers: <pre>';
                print_r($files);
                echo '</pre>';
            }
        } else {
            echo 'Echec Login<br/>';
        }
    } else {
        echo 'Echec connexion<br/>';
    }
}
//testFtp();
