<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CONVERSION CONFIGS', 0, 0, array(), array());

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

$bdb = new BimpDb($db);
$rows = $bdb->getRows('bimpcore_list_config', 1, null, 'array');

foreach ($rows as $r) {
    $data = array(
        'name'               => $r['name'],
        'owner_type'         => $r['owner_type'],
        'id_owner'           => $r['id_owner'],
        'is_default'         => $r['is_default'],
        'obj_module'         => $r['obj_module'],
        'obj_name'           => $r['obj_name'],
        'component_name'     => $r['list_name'],
        'sort_field'         => $r['sort_field'],
        'sort_option'        => $r['sort_option'],
        'sort_way'           => $r['sort_way'],
        'nb_items'           => $r['nb_items'],
        'total_row'          => $r['total_row'],
        'id_default_filters' => $r['id_default_filters']
    );

    switch ([$r['list_type']]) {
        case 'list_table':
            $data['search_open'] = $r['search_open'];
            $data['filters_open'] = $r['filters_open'];
            $data['sheet_name'] = $r['sheet_name'];

            $list_name = $r['list_name'];
            $instance = BimpObject::getInstance($r['obj_module'], $r['obj_name']);
            foreach ($cols as $col_name) {
                $field = $instance->getConf('lists/' . $list_name . '/cols/' . $col_name . '/field', $instance->getConf('lists_cols/' . $col_name . '/field', ''));
                $child = $instance->getConf('lists/' . $list_name . '/cols/' . $col_name . '/field', $instance->getConf('lists_cols/' . $col_name . '/field', ''));

                $new_col_name = '';
                if ($field) {
                    if ($child) {
                        $new_col_name = $child . ':';
                    }
                    $new_col_name .= $field;
                } else {
                    $new_col_name = $col_name;
                }
            }
            break;

        case 'stats_list':
            $data['cols'] = $r['cols'];
            break;
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
