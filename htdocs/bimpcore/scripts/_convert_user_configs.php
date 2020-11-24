<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CONVERSION CONFIGS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

//convertFiltersConfigs();
//convertFiltersConfigs();

echo 'Script désactvé';

function convertListsConfigs($bdb)
{
    global $db;

    $bdb = new BimpDb($db);
    $rows = $bdb->getRows('bimpcore_list_config', 1, null, 'array');

    foreach ($rows as $r) {
        $data = array(
            'name'               => $r['name'],
            'owner_type'         => $r['owner_type'],
            'id_owner'           => $r['id_owner'],
            'id_user_create'     => ($r['owner_type'] === 2 ? (int) $r['id_owner'] : 0),
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

        echo '#' . $r['id'] . ': ';

        switch ($r['list_type']) {
            case 'list_table':
                $data['search_open'] = $r['search_open'];
                $data['filters_open'] = $r['filters_open'];
                $data['sheet_name'] = $r['sheet_name'];

                $instance = BimpObject::getInstance($r['obj_module'], $r['obj_name']);

                if (is_a($instance, 'BimpObject')) {
                    $list_name = $r['list_name'];
                    $new_cols = array();
                    $cols = explode(',', $r['cols']);
                    $cols_options = json_decode($r['cols_options'], 1);


                    foreach ($cols as $col_name) {
                        $list_path = 'lists/' . $list_name . '/cols/' . $col_name . '/';
                        $col_path = 'lists_cols/' . $col_name . '/';
                        $field = $instance->getConf($list_path . 'field', $instance->getConf($col_path . 'field', ''));
                        $child = $instance->getConf($list_path . 'child', $instance->getConf($col_path . 'child', ''));
                        $label = BimpTools::getArrayValueFromPath($cols_options, $col_name . '/label', $instance->getConf($list_path . 'label', $instance->getConf($col_path . 'label', '')));

                        if (!$label && $field) {
                            if ($child) {
                                $child_obj = $instance->getChildObject($child);
                                if (is_a($child_obj, 'BimpObject')) {
                                    if ($child_obj->field_exists($field)) {
                                        $label = $child_obj->getConf('fields/' . $field . '/label', $col_name);
                                    }
                                }
                            } else {
                                if ($instance->field_exists($field)) {
                                    $label = $instance->getConf('fields/' . $field . '/label', $col_name);
                                }
                            }
                        }
                        $new_col_name = '';
                        if ($field) {
                            if ($child) {
                                $new_col_name = $child . ':';
                            }
                            $new_col_name .= $field;
                        } else {
                            $new_col_name = $col_name;
                        }

                        $new_cols[$new_col_name] = array(
                            'label'      => $label,
                            'csv_option' => BimpTools::getArrayValueFromPath($cols_options, $col_name . '/csv_display', '')
                        );
                    }

                    $data['cols'] = json_encode($new_cols);
                } else {
                    $data['cols'] = '';
                }

                if ($bdb->insert('buc_list_table_config', $data) <= 0) {
                    echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
                } else {
                    echo '<span class="success">[OK]</span>';
                }
                break;

            case 'stats_list':
                $data['cols'] = $r['cols'];

                if ($bdb->insert('buc_stats_list_config', $data) <= 0) {
                    echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
                } else {
                    echo '<span class="success">[OK]</span>';
                }
                break;

            default:
                echo '<span class="danger">TYPE INCONNU: ' . $r['list_type'] . '</span>';
        }

        echo '<br/>';
    }
}

function convertFiltersConfigs()
{
    global $db;

    $bdb = new BimpDb($db);
    $rows = $bdb->getRows('bimpcore_list_filters', 1, null, 'array');

    foreach ($rows as $r) {
        echo '#' . $r['id'] . ': ';
        $data = array(
            'name'           => $r['name'],
            'owner_type'     => $r['owner_type'],
            'id_owner'       => $r['id_owner'],
            'is_default'     => 0,
            'id_user_create' => ((int) $r['id_user_create'] ? (int) $r['id_user_create'] : ((int) $r['owner_type'] == 2 ? (int) $r['id_owner'] : 0)),
            'obj_module'     => $r['obj_module'],
            'obj_name'       => $r['obj_name']
        );

        $obj = BimpObject::getInstance($r['obj_module'], $r['obj_name']);

        if (is_a($obj, 'BimpObject')) {
            $filters = array();
            $new_filters = array();

            $incl = json_decode($r['filters'], 1);
            $excl = json_decode($r['excluded'], 1);

            foreach ($incl as $filter_name => $values) {
                if (!isset($filters[$filter_name])) {
                    $filters[$filter_name] = array();
                }

                $filters[$filter_name]['values'] = $values;
            }

            foreach ($excl as $filter_name => $values) {
                if (!isset($filters[$filter_name])) {
                    $filters[$filter_name] = array();
                }

                $filters[$filter_name]['excluded_values'] = $values;
            }

            $filter_path_base = 'filters_panel/' . $r['panel_name'] . '/filters/';

            foreach ($filters as $filter_name => $values) {
                $new_filter_name = '';
                $filter_path = $filter_path_base . $filter_name / '/';

                $field = $obj->getConf($filter_path . 'field');
                $child = $obj->getConf($filter_path . 'child');

                if ($field) {
                    if ($child) {
                        $new_filter_name .= $child . ':';
                    }
                    $new_filter_name .= $field;
                } else {
                    $new_filter_name = $filter_name;
                }

                $new_filters[$new_filter_name] = $values;
            }

            $data['filters'] = json_encode($new_filters);

            if ($bdb->insert('buc_list_filters', $data) <= 0) {
                echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
            } else {
                echo '<span class="success">[OK]</span>';
            }
        } else {
            echo '<span class="danger">INSTANCE INVALIDE</span>';
        }

        echo '<br/>';
    }
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
