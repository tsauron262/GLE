<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

ignore_user_abort(0);

top_htmlhead('', 'CONVERSION REMISES GLOBALES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(0);

//processPropales($bdb);
//processCommandes($bdb);
//processFactures($bdb);

function processPropales($bdb)
{
    $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');

    $rows = $instance->getList(array(
        'remise_globale' => array(
            'operator' => '!=',
            'value'    => 0
        ),
//        'rowid'          => 283262
            ), null, null, 'id', 'asc', 'array', array(
        'rowid', 'remise_globale', 'remise_globale_label'
    ));

    foreach ($rows as $r) {
        $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $r['rowid']);

        if (BimpObject::objectLoaded($propal)) {
            echo '<br/>MAJ PROPALE ' . $r['rowid'] . '<br/>';

            $total_ttc = $propal->getTotalTtcWithoutRemises(true);
            $amount = round($total_ttc * ($r['remise_globale'] / 100), 2);

            echo 'Total TTC INITIAL: ' . $propal->getTotalTtc() . '<br/>';
            echo 'RG %: ' . $r['remise_globale'] . '<br/>';
            echo 'Montant RG: ' . $amount . '<br/>';

            if (!$r['remise_globale_label']) {
                $r['remise_globale_label'] = 'Remise exceptionnelle sur l\'intégralité de la proposition commerciale';
            }

            $data = array(
                'obj_type' => Bimp_Propal::$element_name,
                'id_obj'   => (int) $r['rowid'],
                'label'    => $r['remise_globale_label'],
                'type'     => 'amount',
                'amount'   => $amount
            );

            if ((int) $propal->getData('fk_statut') === 0) {
                $rg = BimpObject::getInstance('bimpcommercial', 'RemiseGlobale');

                $rg->validateArray($data);

                $rg_warnings = array();
                $rg_errors = $rg->create($rg_warnings, true);

                if (count($rg_errors)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($rg_errors, 'Propal #' . $propal->id . ': échec de la création de la remise globale'));
                } else {
                    echo '<span class="success">OK</span><br/>';
                    $bdb->update('propal', array(
                        'remise_globale' => 0
                            ), '`rowid` = ' . (int) $propal->id);
                }

                if (count($rg_warnings)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($rg_errors, 'Propal #' . $propal->id . ': erreurs lors de la création de la remise globale'), 'warning');
                }
            } else {
                $id_rg = (int) $bdb->insert('bimp_remise_globale', $data, true);

                if (!$id_rg) {
                    echo BimpRender::renderAlerts('Propal #' . $propal->id . ': échec de l\'insertion de la remise globale en base - ' . $bdb->db->lasterror());
                } else {
                    echo '<span class="success">OK</span><br/>';
                    $bdb->update('propal', array(
                        'remise_globale' => 0
                            ), '`rowid` = ' . (int) $propal->id);

                    $lines = $propal->getLines('not_text');
                    $total_lines = 0;

                    foreach ($lines as $line) {
                        if ($line->isRemisable()) {
                            $total_lines += (float) $line->getTotalTtcWithoutRemises();
                        }
                    }

                    if ($total_lines) {
                        $line_rate = ($amount / $total_lines) * 100;

                        foreach ($lines as $line) {
                            if ($line->isRemisable()) {
                                if (!$bdb->insert('object_line_remise', array(
                                            'id_object_line'    => (int) $line->id,
                                            'object_type'       => $line::$parent_comm_type,
                                            'id_remise_globale' => $id_rg,
                                            'label'             => 'Part de la remise globale "' . $r['remise_globale_label'] . '"',
                                            'type'              => 1,
                                            'percent'           => $line_rate
                                        ))) {
                                    echo BimpRender::renderAlerts('Propal #' . $propal->id . ' - ligne n°' . $line->getData('position') . ': échec de l\'insertion de la remise - ' . $bdb->db->lasterror());
                                } else {
                                    $remises_infos = $line->getRemiseTotalInfos(true);
                                    if ((float) $line->remise !== (float) $remises_infos['total_percent']) {
                                        echo BimpRender::renderAlerts('Ligne n° ' . $line->getData('position') . ' - Ecart: ' . $line->remise . ' => ' . $remises_infos['total_percent'], 'warning');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            unset($propal);
            BimpCache::$cache = array();
            $propal = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $r['rowid']);
            $lines = $propal->getLines('not_text');
            $total_ttc = 0;
            foreach ($lines as $line) {
                $total_ttc += (float) $line->getTotalTTC();
            }
            echo 'TOTAL TTC FINAL: ' . $total_ttc . '<br/>';
        }

        unset($propal);
        BimpCache::$cache = array();
    }
}

function processCommandes($bdb)
{
    $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');

    $rows = $instance->getList(array(
        'remise_globale' => array(
            'operator' => '!=',
            'value'    => 0
        ),
//        'rowid'          => 96398
            ), null, null, 'id', 'asc', 'array', array(
        'rowid', 'remise_globale', 'remise_globale_label'
    ));

//    echo '<pre>';
//    print_r($rows);
//    exit;

    foreach ($rows as $r) {
        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['rowid']);

        if (BimpObject::objectLoaded($commande)) {
            echo '<br/>MAJ COMMANDE ' . $r['rowid'] . '<br/>';

            $total_ttc = $commande->getTotalTtcWithoutRemises(true);
            $tot_init = (float) $commande->getTotalTtc();
            $amount = round($total_ttc * ($r['remise_globale'] / 100), 2);

            echo '<span class="bold">';
            echo 'Total TTC INITIAL: ' . $tot_init . '<br/>';
            echo '</span>';
            echo 'RG %: ' . $r['remise_globale'] . '<br/>';
            echo 'Montant RG: ' . $amount . '<br/>';

            $data = array(
                'obj_type' => Bimp_Commande::$element_name,
                'id_obj'   => (int) $r['rowid'],
                'label'    => $r['remise_globale_label'],
                'type'     => 'amount',
                'amount'   => $amount
            );

            if ((int) $commande->getData('fk_statut') === 0) {
                $rg = BimpObject::getInstance('bimpcommercial', 'RemiseGlobale');

                $rg->validateArray($data);

                $rg_warnings = array();
                $rg_errors = $rg->create($rg_warnings, true);

                if (count($rg_errors)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($rg_errors, 'Commande #' . $commande->id . ': échec de la création de la remise globale'));
                } else {
                    echo '<span class="success">OK</span><br/>';
                    $bdb->update('commande', array(
                        'remise_globale' => 0
                            ), '`rowid` = ' . (int) $commande->id);
                }

                if (count($rg_warnings)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($rg_errors, 'Commande #' . $commande->id . ': erreurs lors de la création de la remise globale'), 'warning');
                }
            } else {
                $id_rg = (int) $bdb->insert('bimp_remise_globale', $data, true);

                if (!$id_rg) {
                    echo BimpRender::renderAlerts('Commande #' . $commande->id . ': échec de l\'insertion de la remise globale en base - ' . $bdb->db->lasterror());
                } else {
                    echo '<span class="success">OK</span><br/>';
                    $bdb->update('commande', array(
                        'remise_globale' => 0
                            ), '`rowid` = ' . (int) $commande->id);

                    $lines = $commande->getLines('not_text');
                    $total_lines = 0;

                    foreach ($lines as $line) {
                        if ($line->isRemisable()) {
                            $total_lines += (float) $line->getTotalTtcWithoutRemises();
                        }
                    }

                    if ($total_lines) {
                        $line_rate = ($amount / $total_lines) * 100;

                        foreach ($lines as $line) {
                            if ($line->isRemisable()) {
                                if (!$bdb->insert('object_line_remise', array(
                                            'id_object_line'    => (int) $line->id,
                                            'object_type'       => $line::$parent_comm_type,
                                            'id_remise_globale' => $id_rg,
                                            'label'             => 'Part de la remise globale "' . $r['remise_globale_label'] . '"',
                                            'type'              => 1,
                                            'percent'           => $line_rate
                                        ))) {
                                    echo BimpRender::renderAlerts('Commande #' . $commande->id . ' - ligne n°' . $line->getData('position') . ': échec de l\'insertion de la remise - ' . $bdb->db->lasterror());
                                } else {
                                    $remises_infos = $line->getRemiseTotalInfos(true);
                                    if ((float) $line->remise !== (float) $remises_infos['total_percent']) {
                                        echo BimpRender::renderAlerts('Ligne n° ' . $line->getData('position') . ' - Ecart: ' . $line->remise . ' => ' . $remises_infos['total_percent'], 'warning');
                                    }
                                }
                            }
                        }
                    }

                    // Traitement des lignes de factures associées: 
                    $ok = true;
                    foreach ($lines as $line) {
                        if ($line->isRemisable()) {
                            $fac_lines = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_FactureLine', array(
                                        'linked_object_name' => 'commande_line',
                                        'linked_id_object'   => (int) $line->id
                            ));

                            foreach ($fac_lines as $fac_line) {
//                                echo 'FAC LINE #' . $fac_line->id . '<br/>';
                                $remises = BimpCache::getBimpObjectObjects('bimpcommercial', 'ObjectLineRemise', array(
                                            'id_object_line'    => (int) $fac_line->id,
                                            'object_type'       => $fac_line::$parent_comm_type,
                                            'is_remise_globale' => 1
                                ));

                                foreach ($remises as $remise) {
//                                    echo 'MAJ REMISE #' . $remise->id . ': ';
                                    if ($bdb->update('object_line_remise', array(
                                                'linked_id_remise_globale' => $id_rg,
                                                'is_remise_globale'        => 0
                                                    ), '`id` = ' . (int) $remise->id) <= 0) {
                                        echo BimpRender::renderAlerts('Commande #' . $commande->id . ' échec de la modif de la remise fac line #' . $fac_line->id . ' - ' . $bdb->db->lasterror());
                                        $ok = false;
                                    }
//                                    else {
//                                        echo 'OK <br/>';
//                                    }
                                }
                            }
                        }
                    }

                    if ($ok) {
                        $commande->processFacturesRemisesGlobales();
                    }
                }
            }
            unset($commande);
            BimpCache::$cache = array();
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['rowid']);
            $lines = $commande->getLines('not_text');
            $total_ttc_final = 0;
            foreach ($lines as $line) {
                $total_ttc_final += (float) $line->getTotalTTC();
            }
            $class = ((float) round($tot_init, 2) != (float) round($total_ttc_final, 2) ? 'danger' : 'success');
            echo '<span class="' . $class . '">';
            echo 'TOTAL TTC FINAL: ' . $total_ttc_final . '<br/>';
            echo '</span>';
        }

        unset($commande);
        BimpCache::$cache = array();
    }
}

function processFactures($bdb)
{
    $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

    $rows = $instance->getList(array(
        'remise_globale' => array(
            'operator' => '!=',
            'value'    => 0
        ),
//        'rowid'          => 227287
            ), null, null, 'id', 'asc', 'array', array(
        'rowid', 'remise_globale', 'remise_globale_label'
    ));

    foreach ($rows as $r) {
        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);

        if (BimpObject::objectLoaded($facture)) {
            echo '<br/>MAJ FACTURE ' . $r['rowid'] . '<br/>';

            $total_ttc = $facture->getTotalTtcWithoutRemises(true);
            $tot_init = (float) $facture->getTotalTtc();
            $amount = round($total_ttc * ($r['remise_globale'] / 100), 2);

            echo '<span class="bold">';
            echo 'Total TTC INITIAL: ' . $tot_init . '<br/>';
            echo '</span>';
            echo 'RG %: ' . $r['remise_globale'] . '<br/>';
            echo 'Montant RG: ' . $amount . '<br/>';            

            $data = array(
                'obj_type' => Bimp_Facture::$element_name,
                'id_obj'   => (int) $r['rowid'],
                'label'    => $r['remise_globale_label'],
                'type'     => 'amount',
                'amount'   => $amount
            );

            if ((int) $facture->getData('fk_statut') === 0) {
                $rg = BimpObject::getInstance('bimpcommercial', 'RemiseGlobale');

                $rg->validateArray($data);

                $rg_warnings = array();
                $rg_errors = $rg->create($rg_warnings, true);

                if (count($rg_errors)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($rg_errors, 'Facture #' . $facture->id . ': échec de la création de la remise globale'));
                } else {
                    echo '<span class="success">OK</span><br/>';
                    $bdb->update('facture', array(
                        'remise_globale' => 0
                            ), '`rowid` = ' . (int) $facture->id);
                }

                if (count($rg_warnings)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($rg_errors, 'Facture #' . $facture->id . ': erreurs lors de la création de la remise globale'), 'warning');
                }
            } else {
                $id_rg = (int) $bdb->insert('bimp_remise_globale', $data, true);

                if (!$id_rg) {
                    echo BimpRender::renderAlerts('Facture #' . $facture->id . ': échec de l\'insertion de la remise globale en base - ' . $bdb->db->lasterror());
                } else {
                    echo '<span class="success">OK</span><br/>';
                    $bdb->update('facture', array(
                        'remise_globale' => 0
                            ), '`rowid` = ' . (int) $facture->id);

                    $lines = $facture->getLines('not_text');
                    $total_lines = 0;

                    foreach ($lines as $line) {
                        if ($line->isRemisable()) {
                            $total_lines += (float) $line->getTotalTtcWithoutRemises();
                        }
                    }

                    if ($total_lines) {
                        $line_rate = ($amount / $total_lines) * 100;

                        foreach ($lines as $line) {
                            if ($line->isRemisable()) {
                                if (!$bdb->insert('object_line_remise', array(
                                            'id_object_line'    => (int) $line->id,
                                            'object_type'       => $line::$parent_comm_type,
                                            'id_remise_globale' => $id_rg,
                                            'label'             => 'Part de la remise globale "' . $r['remise_globale_label'] . '"',
                                            'type'              => 1,
                                            'percent'           => $line_rate
                                        ))) {
                                    echo BimpRender::renderAlerts('Facture #' . $facture->id . ' - ligne n°' . $line->getData('position') . ': échec de l\'insertion de la remise - ' . $bdb->db->lasterror());
                                } else {
                                    $remises_infos = $line->getRemiseTotalInfos(true);
                                    if ((float) $line->remise !== (float) $remises_infos['total_percent']) {
                                        echo BimpRender::renderAlerts('Ligne n° ' . $line->getData('position') . ' - Ecart: ' . $line->remise . ' => ' . $remises_infos['total_percent'], 'warning');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            unset($facture);
            BimpCache::$cache = array();
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);
            $lines = $facture->getLines('not_text');
            $total_ttc_final = 0;
            foreach ($lines as $line) {
                $total_ttc_final += (float) $line->getTotalTTC();
            }
            $class = ((float) round($tot_init, 2) != (float) round($total_ttc_final, 2) ? 'danger' : 'success');
            echo '<span class="' . $class . '">';
            echo 'TOTAL TTC FINAL: ' . $total_ttc_final . '<br/>';
            echo '</span>';
        }

        unset($facture);
        BimpCache::$cache = array();
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();