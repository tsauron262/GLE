<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'EXPORT CLIENT', 0, 0, array(), array());

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
        'factures_credits' => 'Factures pour crédits clients'
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

$filename = '';
$headers = null;
$rows = array();

switch ($type) {
    case 'factures_credits':
        $filename = 'factures_credits';

        $sql = '';
        $fields = array('facnumber', 'type', 'datef', 'total', 'fk_cond_reglement as cond');
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
                        'not_in' => "'CO', 'I', 'M'"
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
            'ca'       => 'Total CA HT',
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

        $def_encours = BimpCore::getConf('societe_default_outstanding_limit', 4000);
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
                    'nb_t1'    => 0,
                    'nb_t2'    => 0,
                    'nb_t3'    => 0,
                    'tot_t1'   => 0,
                    'tot_t2'   => 0,
                    'tot_t3'   => 0,
                    'factures' => ''
                );
            }

            $tot = (float) $r['total'];
            $clients[(int) $r['id_client']]['ca'] += $tot;
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
            $clients[(int) $r['id_client']]['factures'] .= $r['facnumber'] . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($types[$r['type']]) ? $types[$r['type']] : $r['type']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= $dt->format('d.m.Y') . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($secteurs[$r['secteur']]) ? $secteurs[$r['secteur']] : $r['secteur']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= (isset($zones[$r['zone_vente']]) ? $zones[$r['zone_vente']] : $r['zone_vente']) . ' / ';
            $clients[(int) $r['id_client']]['factures'] .= price($r['total']) . ' / ';
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
                'encours'  => $str_replace('.', ',', (string) $client['encours']),
                'ncs'      => $client['ncs'],
                'ca'       => str_replace('.', ',', (string) $client['ca']),
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