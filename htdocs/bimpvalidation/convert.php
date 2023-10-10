<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once '../bimpcore/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CONVERSION', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$bdb = BimpCache::getBdb();

$rows = $bdb->getRows('demande_validate_comm', 'status = 0', null, 'array');

foreach ($rows as $r) {
    $type_validation = '';
    $type_object = '';

    switch ((int) $r['type']) {
        case 0:
            $type_validation = 'fin';
            break;

        case 1:
            $type_validation = 'comm';
            break;

        case 2:
            $type_validation = 'rtp';
            break;
    }

    switch ((int) $r['type_de_piece']) {
        case 0:
            $type_object = 'propal';
            break;

        case 1:
            $type_object = 'facture';
            break;

        case 2:
            $type_object = 'commande';
            break;

        case 3:
            $type_object = 'contrat';
            break;
    }

    $data = array(
        'status'           => 0,
        'type_validation'  => $type_validation,
        'type_object'      => $type_object,
        'id_object'        => (int) $r['id_piece'],
        'id_user_demande'  => (int) $r['id_user_ask'],
        'validation_users' => '[' . (int) $r['id_user_affected'] . ']',
        'id_user_affected' => (int) $r['id_user_affected'],
        'id_user_validate' => (int) $r['id_user_valid'],
        'comment'          => '',
        'date_create'      => $r['date_create'],
//        'date_update'      => $r['date_update'],
        'user_create'      => $r['user_create'],
//        'user_update'      => $r['user_update']
    );
    $bdb->insert('bv_demande', $data);

    $rows2 = $bdb->getRows('demande_validate_comm', 'type_de_piece = ' . $r['type_de_piece'] . ' AND id_piece = ' . $r['id_piece'] . ' AND status != 0', null, 'array');

    if (!empty($rows2)) {
//        echo '<pre>';
//        print_r($rows2);
//        echo '</pre>';

        foreach ($rows2 as $r2) {
            $type_validation = '';
            $type_object = '';

            switch ((int) $r2['type']) {
                case 0:
                    $type_validation = 'fin';
                    break;

                case 1:
                    $type_validation = 'comm';
                    break;

                case 2:
                    $type_validation = 'rtp';
                    break;
            }

            switch ((int) $r2['type_de_piece']) {
                case 0:
                    $type_object = 'propal';
                    break;

                case 1:
                    $type_object = 'facture';
                    break;

                case 2:
                    $type_object = 'commande';
                    break;

                case 3:
                    $type_object = 'contrat';
                    break;
            }

            $status = 0;
            switch ((int) $r2['status']) {
                case 1:
                    $status = 1;
                    break;

                case 2:
                    $status = -1;
                    break;
            }

            $data = array(
                'status'           => $status,
                'type_validation'  => $type_validation,
                'type_object'      => $type_object,
                'id_object'        => (int) $r2['id_piece'],
                'id_user_demande'  => (int) $r2['id_user_ask'],
                'validation_users' => '[' . (int) $r2['id_user_affected'] . ']',
                'id_user_affected' => (int) $r2['id_user_affected'],
                'id_user_validate' => (int) $r2['id_user_valid'],
                'date_validate'    => $r2['date_valid'],
                'date_create'      => $r2['date_create'],
//        'date_update'      => $r2['date_update'],
                'user_create'      => $r2['user_create'],
//        'user_update'      => $r2['user_update']
            );

            $bdb->insert('bv_demande', $data);
//            break;
        }
    }
//    break;
}

echo '</body></html>';

//llxFooter();
