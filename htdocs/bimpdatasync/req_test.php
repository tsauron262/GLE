<?php

require_once '../main.inc.php';
ini_set('display_errors', 1);
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/BDS_Lib.php';

//ini_set('display_errors', 1);

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

//testReq();

function installProcess()
{
    BDS_Process::$debug_mod = true;
    include_once __DIR__ . '/classes/process_overrides/BDS_PS_SyncProcess.php';
    BDS_PS_SyncProcess::install();
}

function testCommande($id)
{
    if (!class_exists('Commande')) {
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
    }

    global $db;

    $comm = new Commande($db);
    $comm->fetch($id);
    $products = $comm->fetch_lines(0);
    echo '<pre>';
    print_r($comm->lines);
    exit;
}

function testFournPrice()
{
    $fk_fournprice = null;
    $pa_ht = 0;

    global $db;
    $bdb = new BDSDb($db);

    $where = '`fk_product` = 6375';
    $rows = $bdb->getRows('product_fournisseur_price', $where, null, 'object', array(
        'rowid', 'unitprice'
    ));

    if (!is_null($rows)) {
        foreach ($rows as $r) {
            if (!$pa_ht ||  ((float) $r->unitprice < (float) $pa_ht)) {
                $pa_ht = (float) $r->unitprice;
                $fk_fournprice = $r->rowid;
            }
        }
    }

    echo $pa_ht . ', ' . $fk_fournprice;
}

function testCron()
{
    require_once './class/CronExec.class.php';
    global $db;
    $cronExec = new CronExec($db);
    $cronExec->execute(1);
}
