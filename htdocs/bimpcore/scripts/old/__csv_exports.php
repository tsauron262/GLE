<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'EXPORTS CSV', 0, 0, array(), array());

ignore_user_abort(0);

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

$bdb = new BimpDb($db);

$type = BimpTools::getValue('type', '');

if (!$type) {
    $types = array(
        'factures_credits' => 'Factures pour crédits clients',
        'stats_vente'      => 'States ventes sur 12 mois',
        'ventes_prods'     => 'Ventes produits',
        'taux_services'    => 'Pourcentage de vente de service par commerciaux'
    );

    $path = pathinfo(__FILE__);

    foreach ($types as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?type=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

BimpCore::setMaxExecutionTime(12000);

$filename = '';
$headers = null;
$rows = array();

switch ($type) {
    case 'factures_credits':
        $filename = 'factures_credits';

        $sql = '';
        $fields = array('ref', 'type', 'datef', 'total_ht', 'fk_cond_reglement as cond');
        $fields = array_merge($fields, array('fef.type as secteur', 'fef.zone_vente'));
        $fields = array_merge($fields, array('s.rowid as id_client', 's.nom', 's.code_client', 's.siren', 's.code_compta', 's.fk_pays', 's.outstanding_limit'));
        $fields = array_merge($fields, array('sef.secteuractivite as sa', 'sef.notecreditsafe'));

        $sql .= BimpTools::getSqlSelect($fields, 'f');

        $sql .= BimpTools::getSqlFrom('facture', array(
                    array('alias' => 'fef', 'table' => 'facture_extrafields', 'on' => 'f.rowid = fef.fk_object'),
                    array('alias' => 's', 'table' => 'societe', 'on' => 's.rowid = f.fk_soc'),
                    array('alias' => 'sef', 'table' => 'societe_extrafields', 'on' => 's.rowid = sef.fk_object')
                        ), 'f');

        $sql .= BimpTools::getSqlWhere(array(
                    'f.type'       => array(
                        'in' => array(0, 1, 2)
                    ),
                    'f.fk_statut'  => array(
                        'in' => array(1, 2)
                    ),
                    'fef.type'     => array(
                        'not_in' => "'CO', 'I', 'M', 'S'"
                    ),
                    'f.date_valid' => array(
                        'min' => '2019-07-01 00:00:00',
                        'max' => '2020-06-30 23:59:59'
                    ),
                    's.fk_typent'  => array(
                        'not_in' => array(5, 8)
                    )
                        ), 'f');

        $result = $bdb->executeS($sql, 'array');

        if (is_null($result)) {
            echo $bdb->db->lasterror() . '<br/><br/>';
            exit;
        }

        if (empty($result)) {
            echo 'Aucun résultat';
            exit;
        }

        echo 'NB res: ' . count($result) . ' <br/>';
        $total_general = 0;

        $headers = array(
            'nom'      => 'Nom du client',
            'ref'      => 'Numéro client',
            'code'     => 'Code comptable client',
            'siren'    => 'Siren',
            'pays'     => 'Pays',
            'sa'       => 'Secteur d’activité',
            'encours'  => 'Encours autorisé',
            'ncs'      => 'Note CréditSafe',
            'ca'       => 'Total CA HT (Hors Partner)',
            'ca_bp'    => 'Total CA HT (Partner)',
            'nb_t1'    => 'Nb facs < 4000 €',
            'tot_t1'   => 'Total facs < 4000 €',
            'nb_t2'    => 'Nb facs >= 4000 € et < 7000',
            'tot_t2'   => 'Total facs >= 4000 € et < 7000',
            'nb_t3'    => 'Nb facs >= 7000 €',
            'tot_t3'   => 'Total facs => 7000 €',
            'factures' => 'Détail factures (ref / type / date / canal / zone / montant / conditions réglement)'
//            'Zone de Vente',
//            'Canal de Vente',
//            'N° facture',
//            'Type facture',
//            'Date facturation',
//            'Montant HT',
//            'Conditions de paiement'
        );

        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        $countries = BimpCache::getCountriesArray();
        $secteurs = BimpCache::getSecteursArray();
        $zones = Bimp_Facture::$zones_vente;
        $conds = BimpCache::getCondReglementsArray();
        $types = Bimp_Facture::$types;
        $secteurs_act = BimpObject::getListExtrafield('secteuractivite', 'societe');

        $def_encours = (float) BimpCore::getConf('societe_default_outstanding_limit');
        $clients = array();

        foreach ($result as $r) {
            if (!isset($clients[(int) $r['id_client']])) {
                $clients[(int) $r['id_client']] = array(
                    'nom'      => $r['nom'],
                    'ref'      => $r['code_client'],
                    'code'     => $r['code_compta'],
                    'siren'    => $r['siren'],
                    'pays'     => (isset($countries[(int) $r['fk_pays']]) ? $countries[(int) $r['fk_pays']] : 'ID GLE: ' . $r['fk_pays']),
                    'encours'  => (!is_null($r['outstanding_limit']) ? $r['outstanding_limit'] : $def_encours),
                    'sa'       => (isset($secteurs_act[(int) $r['sa']]) ? $secteurs_act[(int) $r['sa']] : 'ID GLE: ' . $r['sa']),
                    'ncs'      => $r['notecreditsafe'],
                    'ca'       => 0,
                    'ca_bp'    => 0,
                    'nb_t1'    => 0,
                    'nb_t2'    => 0,
                    'nb_t3'    => 0,
                    'tot_t1'   => 0,
                    'tot_t2'   => 0,
                    'tot_t3'   => 0,
                    'factures' => ''
                );
            }

            $tot = (float) $r['total_ht'];

            if ($r['secteur'] == 'BP') {
                $clients[(int) $r['id_client']]['ca_bp'] += $tot;
            } else {
                $clients[(int) $r['id_client']]['ca'] += $tot;
            }
            $total_general += $tot;

            if ($tot > 0) {
                if ($tot < 4000) {
                    $clients[(int) $r['id_client']]['nb_t1'] += 1;
                    $clients[(int) $r['id_client']]['tot_t1'] += $tot;
                } elseif ($tot >= 4000 && $tot < 7000) {
                    $clients[(int) $r['id_client']]['nb_t2'] += 1;
                    $clients[(int) $r['id_client']]['tot_t2'] += $tot;
                } else {
                    $clients[(int) $r['id_client']]['nb_t3'] += 1;
                    $clients[(int) $r['id_client']]['tot_t3'] += $tot;
                }
            } else {
                if ($tot > -4000) {
                    $clients[(int) $r['id_client']]['nb_t1'] += 1;
                    $clients[(int) $r['id_client']]['tot_t1'] += $tot;
                } elseif ($tot <= -4000 && $tot > -7000) {
                    $clients[(int) $r['id_client']]['nb_t2'] += 1;
                    $clients[(int) $r['id_client']]['tot_t2'] += $tot;
                } else {
                    $clients[(int) $r['id_client']]['nb_t3'] += 1;
                    $clients[(int) $r['id_client']]['tot_t3'] += $tot;
                }
            }

            if ($clients[(int) $r['id_client']]['factures']) {
                $clients[(int) $r['id_client']]['factures'] .= "\n";
            }

//            ref / type / date / canal / zone / montant / conditions réglement

            $dt = new DateTime($r['datef']);
            $clients[(int) $r['id_client']]['factures'] .= $r['ref'] . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($types[$r['type']]) ? $types[$r['type']]['label'] : $r['type']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= $dt->format('d.m.Y') . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($secteurs[$r['secteur']]) ? $secteurs[$r['secteur']] : $r['secteur']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($zones[$r['zone_vente']]) ? $zones[$r['zone_vente']] : $r['zone_vente']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= price($r['total_ht']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($conds[$r['cond']]) ? $conds[$r['cond']] : 'ID GLE: ' . $r['cond']);
        }

        echo 'TOTAL: ' . BimpTools::displayMoneyValue($total_general) . '<br/>';

        foreach ($clients as $client) {
            $rows[] = array(
                'nom'      => $client['nom'],
                'ref'      => $client['ref'],
                'code'     => $client['code'],
                'siren'    => $client['siren'],
                'pays'     => $client['pays'],
                'sa'       => $client['sa'],
                'encours'  => str_replace('.', ',', (string) $client['encours']),
                'ncs'      => $client['ncs'],
                'ca'       => str_replace('.', ',', (string) $client['ca']),
                'ca_bp'    => str_replace('.', ',', (string) $client['ca_bp']),
                'nb_t1'    => str_replace('.', ',', (string) $client['nb_t1']),
                'tot_t1'   => str_replace('.', ',', (string) $client['tot_t1']),
                'nb_t2'    => str_replace('.', ',', (string) $client['nb_t2']),
                'tot_t2'   => str_replace('.', ',', (string) $client['tot_t2']),
                'nb_t3'    => str_replace('.', ',', (string) $client['nb_t3']),
                'tot_t3'   => str_replace('.', ',', (string) $client['tot_t3']),
                'factures' => $client['factures']
            );
        }

        break;

    case 'stats_vente':
        $filename = 'stats_ventes';

        $sql = 'SELECT f.rowid, fef.entrepot as id_entrepot, f.datef as date FROM llx_facture f';
        $sql .= ' LEFT JOIN llx_facture_extrafields fef ON f.rowid = fef.fk_object';
        $sql .= ' WHERE f.fk_statut IN (1,2)';
        $sql .= ' AND f.datef > \'2019-06-30\' AND f.datef < \'2020-07-01\'';
        $sql .= ' AND fef.type IN(\'M\')';

        $result = $bdb->executeS($sql, 'array');

        $ventes = array();

        foreach ($result as $r) {
            if ((int) $r['id_entrepot']) {
                if (!isset($ventes[(int) $r['id_entrepot']])) {
                    $ventes[(int) $r['id_entrepot']] = array();
                }

                $dt = new DateTime($r['date']);
                $dt_str = $dt->format('Y-m');

                if (!isset($ventes[(int) $r['id_entrepot']][$dt_str])) {
                    $ventes[(int) $r['id_entrepot']][$dt_str] = 0;
                }

                $ventes[(int) $r['id_entrepot']][$dt_str]++;
            }
        }

//        echo '<pre>';
//        print_r($ventes);
//        exit;

        $headers['ref_ent'] = 'Ref. Entrepôt';
        $headers['lieu'] = 'Lieu';

        $m = 7;
        $y = '2019';

        while (1) {
            $m_str = BimpTools::addZeros((string) $m, 2);
            $headers[$y . '-' . $m_str] = $m_str . ' / ' . $y;
            $m++;

            if ($m > 12) {
                $m = 1;
                $y++;
            }

            if ($m >= 7 && $y >= 2020) {
                break;
            }
        }

        $rows = array();

        $entrepots = array();

        foreach ($bdb->getRows('entrepot', 1, NULL, 'array', array('rowid', 'ref', 'lieu')) as $ent) {
            $entrepots[(int) $ent['rowid']] = array(
                'ref'  => $ent['ref'],
                'lieu' => $ent['lieu']
            );
        }

        foreach ($ventes as $id_entrepot => $ent_ventes) {
            $row = array(
                'ref_ent' => (isset($entrepots[(int) $id_entrepot]) ? $entrepots[(int) $id_entrepot]['ref'] : '#' . $id_entrepot),
                'lieu'    => (isset($entrepots[(int) $id_entrepot]) ? $entrepots[(int) $id_entrepot]['lieu'] : ''),
            );

            foreach ($headers as $code => $label) {
                if (in_array($code, array('ref_ent', 'lieu'))) {
                    continue;
                }

                if (isset($ent_ventes[$code])) {
                    $row[$code] = $ent_ventes[$code];
                } else {

                    $row[$code] = 0;
                }
            }

            $rows[] = $row;
        }
        break;

    case 'ventes_prods':
        $filename = 'ventes_prods_du_01_07_2019_au_31_03_2020';

        $sql = '';
        $fields = array('a.qty', 'a.subprice', 'a.remise_percent');
        $fields = array_merge($fields, array('p.ref'));
        $fields = array_merge($fields, array('pef.collection', 'pef.categorie', 'pef.gamme', 'pef.famille', 'pef.nature'));
        $fields = array_merge($fields, array('s.fk_typent'));

        $sql .= BimpTools::getSqlSelect($fields, 'a');

        $sql .= BimpTools::getSqlFrom('facturedet', array(
                    array('alias' => 'p', 'table' => 'product', 'on' => 'p.rowid = a.fk_product'),
                    array('alias' => 'pef', 'table' => 'product_extrafields', 'on' => 'p.rowid = pef.fk_object'),
                    array('alias' => 'f', 'table' => 'facture', 'on' => 'f.rowid = a.fk_facture'),
                    array('alias' => 's', 'table' => 'societe', 'on' => 's.rowid = f.fk_soc'),
                        ), 'a');

        $sql .= BimpTools::getSqlWhere(array(
                    'f.fk_statut'  => array(
                        'in' => array(1, 2)
                    ),
                    'f.datef'      => array(
                        'min' => '2019-07-01',
                        'max' => '2020-03-31'
                    ),
                    'a.fk_product' => array(
                        'operator' => '>',
                        'value'    => 0
                    ),
                        ), 'a');

//        $sql .= BimpTools::getSqlLimit(100);

        $result = $bdb->executeS($sql, 'array');

        if (is_null($result)) {
            echo $bdb->db->lasterror() . '<br/><br/>';
            exit;
        }

        if (empty($result)) {
            echo 'Aucun résultat';
            exit;
        }

        echo 'NB res: ' . count($result) . ' <br/>';
        $nb = 0;

        $collections = BimpCache::getProductsTagsByTypeArray('collection', false);
        $categories = BimpCache::getProductsTagsByTypeArray('categorie', false);
        $gammes = BimpCache::getProductsTagsByTypeArray('gamme', false);
        $familles = BimpCache::getProductsTagsByTypeArray('famille', false);
        $natures = BimpCache::getProductsTagsByTypeArray('nature', false);

        $headers = array(
            'ref_prod'    => 'Code article',
            'qty'         => 'Qté vendue',
            'pu_ht'       => 'Prix unitaire HT remisé',
            'total_ht'    => 'Total HT remisé',
            'collection'  => 'Collection',
            'categorie'   => 'Catégorie',
            'gamme'       => 'Gamme',
            'famille'     => 'Famille',
            'nature'      => 'Nature',
            'type_client' => 'Type client'
        );

        foreach ($result as $r) {
            $pu_ht = (float) $r['subprice'];

            if ((float) $r['remise_percent']) {
                $pu_ht -= (float) ($pu_ht * (float) $r['remise_percent'] / 100);
            }

            $total_ht = $pu_ht * (float) $r['qty'];

            $rows[] = array(
                'ref_prod'    => $r['ref'],
                'qty'         => $r['qty'],
                'pu_ht'       => str_replace('.', ',', (string) (round($pu_ht, 4))),
                'total_ht'    => str_replace('.', ',', (string) (round($total_ht, 4))),
                'collection'  => (isset($collections[(int) $r['collection']]) ? $collections[(int) $r['collection']] : ((int) $r['collection'] ? '#' . $r['collection'] : '')),
                'categorie'   => (isset($categories[(int) $r['categorie']]) ? $categories[(int) $r['categorie']] : ((int) $r['categorie'] ? '#' . $r['categorie'] : '')),
                'gamme'       => (isset($gammes[(int) $r['gamme']]) ? $gammes[(int) $r['gamme']] : ((int) $r['gamme'] ? '#' . $r['gamme'] : '')),
                'famille'     => (isset($familles[(int) $r['famille']]) ? $familles[(int) $r['famille']] : ((int) $r['famille'] ? '#' . $r['famille'] : '')),
                'nature'      => (isset($natures[(int) $r['nature']]) ? $natures[(int) $r['nature']] : ((int) $r['nature'] ? '#' . $r['nature'] : '')),
                'type_client' => (in_array((int) $r['fk_typent'], array(0, 8)) ? 'PARTICULIER' : 'PRO')
            );

            $nb++;

            echo 'Done: ' . $nb . '<br/>';
            echo 'Rows: ' . count($rows) . '<br/>';
        }
        break;

    case 'taux_services':
        $commercial_fk_type_contact = 50;
        $filename = 'Services par commerciaux';
        $headers = array(
            'comm'    => 'Commercial',
            'ventes'  => 'Total CA HT',
            'service' => 'Total services HT',
            'percent' => 'Pourcentage'
        );

        $sql = BimpTools::getSqlSelect(array(
                    'a.total_ht',
                    'f.ref as fac_ref',
//                    'a.fk_product',
//                    'a.product_type as line_product_type',
//                    'p.fk_product_type as product_type',
                    'ec.fk_socpeople as id_user',
                    'p.ref as ref_prod'
        ));
        $sql .= BimpTools::getSqlFrom('facturedet', array(
                    'p'   => array(
                        'table' => 'product',
                        'on'    => 'p.rowid = a.fk_product'
                    ),
                    'f'   => array(
                        'table' => 'facture',
                        'on'    => 'f.rowid = a.fk_facture'
                    ),
                    'fef' => array(
                        'table' => 'facture_extrafields',
                        'on'    => 'fef.fk_object = a.fk_facture'
                    ),
                    'fef' => array(
                        'table' => 'facture_extrafields',
                        'on'    => 'fef.fk_object = a.fk_facture'
                    ),
                    'ec'  => array(
                        'table' => 'element_contact',
                        'on'    => 'ec.element_id = a.fk_facture'
                    )
        ));
        $sql .= BimpTools::getSqlWhere(array(
                    'a.total_ht'           => array(
                        'operator' => '!=',
                        'value'    => 0
                    ),
                    'a.fk_remise_except'   => 'IS_NULL',
                    'ec.fk_c_type_contact' => $commercial_fk_type_contact,
                    'f.datef'              => array(
                        'min' => '2022-04-01',
                        'max' => '2023-03-31'
                    ),
                    'fef.type'             => array(
                        'in' => array('C', 'E')
                    )
        ));

        $results = $bdb->executeS($sql, 'array');
        $users = array();

        if (is_array($results)) {
            foreach ($results as $r) {
                if (!isset($users[(int) $r['id_user']])) {
                    $users[(int) $r['id_user']] = array(
                        'total'    => 0,
                        'services' => 0
                    );
                }

                $users[(int) $r['id_user']]['total'] += $r['total_ht'];

                if ($r['ref_prod'] && preg_match('/^SERV.+$/', $r['ref_prod'])) {
                    $users[(int) $r['id_user']]['services'] += $r['total_ht'];
                }
            }
        }


        foreach ($users as $id_user => $user_data) {
            if ($user_data['total']) {
                $u = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                $percent = $user_data['services'] / $user_data['total'] * 100;

                $rows[] = array(
                    'comm'    => $u->getName(),
                    'ventes'  => $user_data['total'],
                    'service' => $user_data['services'],
                    'percent' => $percent
                );
            }
        }
        
        break;
}

if (empty($rows)) {
    echo 'Aucun résultat';
    exit;
}

$str = '';

if (!is_null($headers)) {
    $fl = true;
    foreach ($headers as $code => $label) {
        if (!$fl) {
            $str .= ';';
        } else {
            $fl = false;
        }

        $str .= '"' . $label . '"';
    }

    $str .= "\n";
}

foreach ($rows as $r) {
    if (!is_null($headers)) {
        $fl = true;
        foreach ($headers as $code => $label) {
            if (!$fl) {
                $str .= ';';
            } else {
                $fl = false;
            }

            if (isset($r[$code])) {
                $str .= '"' . $r[$code] . '"';
            }
        }

        $str .= "\n";
    } else {
        // todo. 
    }
}

if (!file_put_contents(DOL_DATA_ROOT . '/bimpcore/' . $filename . '.csv', $str)) {
    echo 'Echec de la création du fichier CSV "' . $filename . '.csv"';
} else {
    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities($filename . '.csv');
    echo '<script>';
    echo 'window.open(\'' . $url . '\')';
    echo '</script>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();