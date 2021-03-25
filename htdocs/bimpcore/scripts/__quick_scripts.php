<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

ignore_user_abort(0);

top_htmlhead('', 'QUICK SCRIPTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array(
        'correct_prod_cur_pa'                  => 'Corriger le champs "cur_pa_ht" des produits',
        'check_facs_paiement'                  => 'Vérifier les statuts paiements des factures',
        'check_facs_paiement_rap_inf_one_euro' => 'Vérifier les statuts paiements des factures (Restes à payer < 1€)',
        'check_facs_remain_to_pay'             => 'Recalculer tous les restes à payer',
        'check_clients_solvabilite'            => 'Vérifier les statuts solvabilité des clients',
        'check_commandes_status'               => 'Vérifier les statuts des commandes client',
        'check_commandes_fourn_status'         => 'Vérifier les statuts des commandes fournisseur',
        'change_prods_refs'                    => 'Corriger refs produits',
//        'check_vente_paiements'        => 'Vérifier les paiements des ventes en caisse',
        'check_factures_rg'                    => 'Vérification des Remmises globales factures',
        'traite_obsolete'                      => 'Traitement des produit obsoléte hors stock',
        'cancel_factures'                      => 'Annulation factures',
        'refresh_count_shipped'                => 'Retraitement des lignes fact non livre et inversse',
        'convert_user_configs'                 => 'Convertir les configurations utilisateur vers la nouvelle version',
        'check_list_table_configs'             => 'Vérifier les configurations de liste',
        'check_stocks_mouvements'              => 'Vérifier les mouvements de stock (doublons)',
        'check_limit_client'                   => 'Vérifier les encours credit safe',
        'check_facs_margin'                    => 'Vérifier les marges + revals OK factures',
        'change_sn'                            => 'Changement de SN',
        'secteur_facture_fourn_with_commande_fourn' => 'Secteur fact fourn with comm fourn'
    );


    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

ini_set('max_execution_time', 300);
set_time_limit(300);

switch ($action) {
    case 'secteur_facture_fourn_with_commande_fourn':
        global $db;
        $sql = $db->query("SELECT c.rowid, ref, ce.type, cfe.type as newType FROM `llx_facture_fourn` c, llx_facture_fourn_extrafields ce LEFT JOIN llx_element_element ee ON ee.sourcetype = 'order_supplier' AND targettype = 'invoice_supplier' AND ee.fk_target = ce.fk_object LEFT JOIN llx_commande_fournisseur_extrafields cfe ON ee.fk_source = cfe.fk_object WHERE c.rowid = ce.fk_object AND ce.type IS null AND c.`datec` > '2019-07-01' AND cfe.type != '';");
        while($ln = $db->fetch_object($sql)){
            $db->query("UPDATE llx_facture_fourn_extrafields SET type = '".$ln->newType."' WHERE type is NULL AND fk_object =".$ln->rowid);
        }
        break;
    
    case 'check_limit_client':
        $errors = array();
        $socs = BimpObject::getBimpObjectList('bimpcore', 'Bimp_Societe', array('rowid' => array('custom' => 'a.rowid IN (SELECT DISTINCT(`fk_soc`)  FROM `llx_societe_commerciaux` WHERE `fk_user` = 7)')));
        foreach ($socs as $idSoc) {
            $soc = BimpObject::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $idSoc);
            $data = array();
            $errors = BimpTools::merge_array($errors, $soc->checkSiren('siret', $soc->getData('siret'), $data));
            if (count($data) > 0) {
                $soc->set('notecreditsafe', $data['notecreditsafe']);
                $soc->set('outstanding_limit', $data['outstanding_limit']);
                $soc->set('capital', $data['capital']);
                $soc->set('tva_intra', $data['tva_intra']);
                $soc->set('capital', $data['capital']);
                $errors = BimpTools::merge_array($errors, $soc->update());
            }
            print_r($idSoc . '<br/>');
            print_r($data);
            echo '<br/><br/>';
        }
        print_r($erros);
        break;
    case 'refresh_count_shipped':
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeLine');
        Bimp_CommandeLine::checkAllQties();
        break;
    case 'traite_obsolete':
        global $db;
        $sql = $db->query("SELECT DISTINCT (a.rowid) FROM llx_product a LEFT JOIN llx_product_extrafields ef ON a.rowid = ef.fk_object WHERE (a.stock = '0' || a.stock is null) AND a.tosell IN ('1') AND (ef.famille = 3097) ORDER BY a.ref DESC");
        while ($ln = $db->fetch_object($sql))
            $db->query("UPDATE `llx_product` SET `tosell` = 0, `tobuy` = 0 WHERE rowid = " . $ln->rowid);
        break;

    case 'correct_prod_cur_pa':
        BimpObject::loadClass('bimpcore', 'Bimp_Product');
        Bimp_Product::correctAllProductCurPa(true, true);
        break;

    case 'check_facs_paiement':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkIsPaidAll();
        break;

    case 'check_facs_paiement_rap_inf_one_euro':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkIsPaidAll(array(
            'remain_to_pay' => array(
                'and' => array(
                    array(
                        'operator' => '>',
                        'value'    => 0
                    ),
                    array(
                        'operator' => '<',
                        'value'    => 1
                    )
                )
            )
        ));
        break;

    case 'check_facs_remain_to_pay':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkRemainToPayAll(true);
        break;

    case 'check_commandes_status':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        Bimp_Commande::checkStatusAll();
        break;

    case 'check_commandes_fourn_status':
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeFourn');
        Bimp_CommandeFourn::checkStatusAll();
        break;

    case 'check_clients_solvabilite':
        BimpObject::loadClass('bimpcore', 'Bimp_Societe');
        Bimp_Societe::checkSolvabiliteStatusAll();
        break;

    case 'change_prods_refs':
        $bdb = new BimpDb($db);
        $lines = file(DOL_DOCUMENT_ROOT . '/bimpcore/convert_file.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        foreach ($lines as $line) {
            $data = explode(':', $line);

            if ($data[0] === $data[1]) {
                continue;
            }

            if ($bdb->update('product', array(
                        'ref' => $data[1]
                            ), 'ref = \'' . $data[0] . '\'') < 0) {
                echo 'ECHEC ' . $data[0];
            } else {
                echo 'OK ' . $data[1];
            }

            echo '<br/>';
        }
        break;

    case 'check_factures_rg':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkRemisesGlobalesAll(true, true);
        break;

    case 'cancel_factures':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::cancelFacturesFromRefsFile(DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/factures_to_cancel.txt', true);
        break;

    case 'convert_user_configs':
        if (!(int) BimpCore::getConf('old_user_configs_converted', 0)) {
            echo 'CONVERSION DES FILTRES ENREGISTRES: <br/><br/>';
            $new_filters = convertFiltersConfigs();

            echo '<br/><br/>CONVERSION DES CONFIGS DE LISTE: <br/><br/>';
            convertListsConfigs($new_filters);

            BimpCore::setConf('old_user_configs_converted', 1);
        }
        break;

    case 'check_list_table_configs':
        BimpObject::loadClass('bimpuserconfig', 'ListTableConfig');

        $exec = (int) BimpTools::getValue('exec', 0);

        if (!$exec) {
            $path = pathinfo(__FILE__);
            echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=check_list_table_configs&exec=1" class="btn btn-default">';
            echo 'effectuer les corrections';
            echo '</a>';
            echo '<br/><br/>';
        }

        ListTableConfig::checkAll(true, $exec);
        break;

    case 'check_stocks_mouvements':
        $date_min = BimpTools::getValue('date_min', '');
        $date_max = BimpTools::getValue('date_max', '');

        if (!$date_min || !$date_max) {
            echo BimpRender::renderAlerts('Indiquer date_min et date_max dans l\'url', 'info');
        } else {
            $exec = (int) BimpTools::getValue('exec', 0);

            if (!$exec) {
                $path = pathinfo(__FILE__);
                echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=check_stocks_mouvements&exec=1&date_min=' . $date_min . '&date_max=' . $date_max . '" class="btn btn-default">';
                echo 'effectuer les corrections';
                echo '</a>';
                echo '<br/><br/>';
            }

            BimpObject::loadClass('bimpcore', 'BimpProductMouvement');
            BimpProductMouvement::checkMouvements($date_min, $date_max, true, false);
        }
        break;

    case 'check_facs_margin':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        $errors = Bimp_Facture::checkMarginAll();
        if (count($errors)) {
            echo BimpRender::renderAlerts($errors);
        } else {
            echo '<span class="success">Aucune erreur</span>';
        }
        break;
        
    case 'change_sn':
        $sql = $db->query("SELECT a.serial, a.id FROM ".MAIN_DB_PREFIX."be_equipment a LEFT JOIN llx_be_equipment_place a___places ON a___places.id_equipment = a.id WHERE (a___places.infos LIKE '%FV202000952549%' ESCAPE '$')");
        $i = 0;
        $tabNew = array("DMPDKFVCQ1GC","DMPDKYZYQ1GC","DMQDK05PQ1GC","DMQDKDAPQ1GC","DMQDKDS9Q1GC","DMQDKERUQ1GC","DMPDKKZPQ1GC","DMPDKPX4Q1GC","DMPDKRLWQ1GC","DMPDKTDBQ1GC","DMPDKTNWQ1GC","DMPDKW79Q1GC","DMPDKWM1Q1GC","DMPDKY3NQ1GC","DMQDK0CVQ1GC","DMQDK0NAQ1GC","DMQDK2FVQ1GC","DMQDK6JYQ1GC","DMQDK6VVQ1GC","DMQDK72BQ1GC","DMQDK9EBQ1GC","DMQDK9Z3Q1GC","DMQDKA3VQ1GC","DMQDKBQ1Q1GC","DMQDKBS2Q1GC","DMQDKBSCQ1GC","DMQDKCF3Q1GC","DMQDKCVBQ1GC","DMQDKCVMQ1GC","DMQDKD5QQ1GC","DMQDKDPDQ1GC","DMQDKDSPQ1GC","DMQDKENUQ1GC","DMQDKEQEQ1GC","DMQDKHC0Q1GC","DMQDKHX8Q1GC","DMPDKSUMQ1GC","DMPDKWSGQ1GC","DMPDKX2KQ1GC","DMPDKY3EQ1GC","DMPDKYQ3Q1GC","DMPDKZY7Q1GC","DMQDK09WQ1GC","DMQDK0BHQ1GC","DMQDK2HHQ1GC","DMQDK3G6Q1GC","DMQDK3QLQ1GC","DMQDK5ZXQ1GC","DMQDK5ZZQ1GC","DMQDK681Q1GC","DMQDK6KDQ1GC","DMQDK7YBQ1GC","DMQDK7YNQ1GC","DMQDK87DQ1GC","DMQDK87JQ1GC","DMQDK8SYQ1GC","DMQDK8YTQ1GC","DMQDKAD2Q1GC","DMQDKALCQ1GC","DMQDKC4YQ1GC","DMQDKC67Q1GC","DMQDKCXNQ1GC","DMQDKEUDQ1GC","DMQDKJDVQ1GC","DMQDKJQEQ1GC","DMQDKLPWQ1GC","DMQDK47RQ1GC","DMQDK2UZQ1GC","DMPDKWQJQ1GC","DMPDKQUBQ1GC");
        while ($ln = $db->fetch_object($sql)){
            global $dolibarr_main_url_root;
            die($dolibarr_main_url_root);
//           $db->query("UPDATE ".MAIN_DB_PREFIX."be_equipment SET serial = '".$tabNew[$i]."' WHERE serial = '".$ln->serial."' AND id = ".$ln->id.";");
            $i++;
        }
        break;

    default:
        echo 'Action invalide';
        break;
}

echo '<br/>FIN';

echo '</body></html>';

// FONCTIONS: 


function convertListsConfigs($new_filters = array())
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
            'active_filters'     => $r['active_filters'],
            'id_default_filters' => (isset($new_filters[(int) $r['id_default_filters']]) ? $new_filters[(int) $r['id_default_filters']] : 0)
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
    $new_filters = array();

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
//            $new_filters = array();

            $incl = json_decode($r['filters'], 1);
            $excl = json_decode($r['excluded'], 1);

            if (isset($incl['fields']) && !empty($incl['fields'])) {
                foreach ($incl['fields'] as $filter_name => $values) {
                    if (!isset($filters[$filter_name])) {
                        $filters[$filter_name] = array();
                    }

                    $filters[$filter_name]['values'] = $values;
                }
            }

            if (isset($excl['fields']) && !empty($excl['fields'])) {
                foreach ($excl['fields'] as $filter_name => $values) {
                    if (!isset($filters[$filter_name])) {
                        $filters[$filter_name] = array();
                    }

                    $filters[$filter_name]['excluded_values'] = $values;
                }
            }

            if (isset($incl['children']) && !empty($incl['children'])) {
                foreach ($incl['children'] as $child_name => $child_filters) {
                    foreach ($child_filters as $name => $values) {
                        $filter_name = $child_name . ':' . $name;
                        if (!isset($filters[$filter_name])) {
                            $filters[$filter_name] = array();
                        }
                        $filters[$filter_name]['values'] = $values;
                    }
                }
            }

            if (isset($excl['children']) && !empty($excl['children'])) {
                foreach ($excl['children'] as $child_name => $child_filters) {
                    foreach ($child_filters as $name => $values) {
                        $filter_name = $child_name . ':' . $name;

                        if (!isset($filters[$filter_name])) {
                            $filters[$filter_name] = array();
                        }

                        $filters[$filter_name]['excluded_values'] = $values;
                    }
                }
            }
//
//            $filter_path_base = 'filters_panel/' . $r['panel_name'] . '/filters/';
//
//            foreach ($filters as $filter_name => $values) {
//                $new_filter_name = '';
//                $filter_path = $filter_path_base . $filter_name / '/';
//
//                $field = $obj->getConf($filter_path . 'field');
//                $child = $obj->getConf($filter_path . 'child');
//
//                if ($field) {
//                    if ($child) {
//                        $new_filter_name .= $child . ':';
//                    }
//                    $new_filter_name .= $field;
//                } else {
//                    $new_filter_name = $filter_name;
//                }
//
//                $new_filters[$new_filter_name] = $values;
//            }

            $data['filters'] = json_encode($filters);

            $id_new_filter = $bdb->insert('buc_list_filters', $data, true);

            if ($id_new_filter <= 0) {
                echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
            } else {
                echo '<span class="success">[OK]</span>';

                $new_filters[(int) $r['id']] = $id_new_filter;
            }
        } else {
            echo '<span class="danger">INSTANCE INVALIDE</span>';
        }

        echo '<br/>';
    }

    return $new_filters;
}
