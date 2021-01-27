<?php

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/BTC_export.class.php';

class BTC_export_facture extends BTC_export
{

    public function export($id_facture, $forced, $confFile)
    {

        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

        if (!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file = $this->create_daily_file('vente', null, $confFile['name'], $confFile['dir']);
        } else {
            $file = $this->create_daily_file('vente', $facture->getData('datef'));
        }

        //$file = $this->create_daily_file('vente', $facture->getData('datef'));
        if ($contact = $this->db->getRow('element_contact', 'element_id = ' . $facture->getData('fk_soc') . ' AND fk_c_type_contact = 60')) {
            $id_client_facturation = $contact->fk_socpeople;
        } else {
            $id_client_facturation = $facture->getData('fk_soc');
        }
        $societe = $this->getInstance('bimpcore', 'Bimp_Societe', $facture->getData('fk_soc'));

        $is_client_interco = false;
        $is_vente_ticket = false;
        $compte_general_411 = '41100000';
        $total_ttc_facture = $facture->getData('multicurrency_total_ttc');
        $date_facture = new DateTime($facture->getData('datef'));
        $date_creation = new dateTime($facture->getData('datec'));
        $date_echeance = new DateTime($facture->getData('date_lim_reglement'));
        $id_reglement = ($facture->getData('fk_mode_reglement') > 0) ? $facture->getData('fk_mode_reglement') : 6;
        $reglement = $this->db->getRow('c_paiement', 'id = ' . $id_reglement);
        $inverse = false;

        $use_tva = true;
        $use_d3e = true;

        if ($societe->getData('is_subsidiary')) {
            $compte_general_411 = $societe->getData('accounting_account');
            $is_client_interco = true;
        }

        $compte_general_tva_null = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_vente_tva_null'), $compte_general_411);
        $compte_refact_ht = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_refacturation_ht'), $compte_general_411);
        $compte_refact_ttc = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_refacturation_ttc'), $compte_general_411);
        ;

        switch ($facture->getData('zone_vente')) {
            case 1:
                $compte_general_produit = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_vente_produit_fr'), $compte_general_411);
                $compte_general_service = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_vente_service_fr'), $compte_general_411);
                $compte_general_tva = BimpCore::getConf('BIMPTOCEGID_vente_tva_fr');
                $compte_general_d3e = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_vente_dee_fr'), $compte_general_411);
                $compte_general_port = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_frais_de_port_vente_fr'), $compte_general_411);
                $compte_general_comissions = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_comissions_fr'), $compte_general_411);
                break;
            case 2: case 4:
                $use_d3e = false;
                $use_tva = ($societe->getData('tva_intra')) ? false : true;
                $compte_general_produit = BimpCore::getConf('BIMPTOCEGID_vente_produit_ue');
                $compte_general_service = BimpCore::getConf('BIMPTOCEGID_vente_service_ue');
                $compte_general_tva = BimpCore::getConf('BIMPTOCEGID_vente_tva_ue');
                $compte_general_d3e = BimpCore::getConf('BIMPTOCEGID_vente_dee_ue');
                $compte_general_port = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_frais_de_port_vente_ue'), $compte_general_411);
                $compte_general_comissions = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_comissions_ue'), $compte_general_411);
                break;
            case 3:
                $use_d3e = false;
                $use_tva = false;
                $compte_general_produit = BimpCore::getConf('BIMPTOCEGID_vente_produit_ex');
                $compte_general_service = BimpCore::getConf('BIMPTOCEGID_vente_service_ex');
                $compte_general_port = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_frais_de_port_vente_ex'), $compte_general_411);
                $compte_general_comissions = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_comissions_ex'), $compte_general_411);
                break;
        }

        if ($total_ttc_facture < 0) {
            $inverse = true;
        }
        $sens_parent = $this->get_sens($total_ttc_facture, 'facture', $inverse);

        if ($societe->getData('exported') == 1) {
            $code_auxiliaire = $societe->getData('code_compta');
        } else {
            $export_societe = $this->getInstance('bimptocegid', 'BTC_export_societe');
            $code_auxiliaire = $export_societe->export($societe, 'c', $facture->getData('datef'));
        }

        $label = strtoupper($this->suppr_accents($societe->getData('nom')));
        $bc_vente = $this->getInstance('bimpcaisse', 'BC_Vente');
        if ($bc_vente->find(['id_facture' => $facture->id])) {
            if ($bc_vente->getData('id_client') == 0) {
                $is_vente_ticket = true;
            }
            $id_entrepot = $bc_vente->getData('id_entrepot');
        } else {
            $id_entrepot = 50;
        }
        $entrepot = $this->db->getRow('entrepot', 'rowid = ' . $id_entrepot);
        if ($is_vente_ticket) {
            $code_auxiliaire = $entrepot->compte_aux;
            $label = strtoupper("vente ticket " . $code_auxiliaire);
        }
        
        if($facture->getData('type') == 2 && $facture->getData('fk_facture_source') > 0) {
            $ref_ext = $this->db->getValue('facture', 'facnumber', 'rowid = ' . $facture->getData('fk_facture_source'));
        } else {
            $ref_ext = $facture->getData('facnumber');
        }

        $structure = [
            'journal'           => [($is_client_interco) ? 'VI' : "VTE", 3],
            'date'              => [$date_facture->format('dmY'), 8],
            'type_piece'        => ['FC', 2],
            'compte_general'    => [$compte_general_411, 17],
            'type_de_compte'    => ["X", 1],
            'code_auxiliaire'   => [$code_auxiliaire, 16],
            'next'              => ['', 1],
            'ref_interne'       => [$facture->getData('facnumber'), 35],
            'label'             => [$label, 35],
            'reglement'         => [($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3],
            'echeance'          => [$date_echeance->format('dmY'), 8],
            'sens'              => [$sens_parent, 1],
            'montant'           => [abs(round($total_ttc_facture, 2)), 20, true],
            'type_ecriture'     => [$this->type_ecriture, 1],
            'numero_piece'      => [$facture->id, 8, true],
            'devise'            => ['EUR', 3],
            'taux_dev'          => ['1', 10],
            'code_montant'      => ['E--', 3],
            'montant_2'         => ['', 20],
            'montant_3'         => ['', 20],
            'etablissement'     => ['001', 3],
            'axe'               => ['A1', 2],
            'numero_echeance'   => ['1', 2],
            'ref_externe'       => [$ref_ext, 35],
            'date_ref_externe'  => ['01011900', 8],
            'date_creation'     => [$date_creation->format('dmY'), 8],
            'societe'           => ['', 3],
            'affaire'           => ['', 17],
            'date_taux_dev'     => ['01011900', 8],
            'nouveau_ecran'     => ['N', 3],
            'quantite_1'        => ['', 20],
            'quantite_2'        => ['', 20],
            'qualif_quantite_1' => ['', 3],
            'qualif_quantite_2' => ['', 3],
            'ref_libre'         => ['Export automatique BIMP ERP', 35],
            'tva_encaissement'  => ['-', 1],
            'regime_tva'        => ['CEE', 3],
            'tva'               => ['T', 3],
            'tpf'               => ['N', 3],
            'contre_partie'     => ['', 17],
            'vide'              => ['', 606],
            'lettrage_dev'      => ['-', 1],
            'lettrage_euro'     => ['X', 1],
            'etat_lettrage'     => ['AL', 2],
            'vide_2'            => ['', 153],
            'valide'            => ['-', 1],
            'before'            => ['', 1],
            'date_debut'        => ['', 8],
            'date_fin'          => ['', 8]
        ];

        $writing_ligne_client = false;
        $total_lignes_facture = 0;
        $d3e = 0;
        $lignes = [];
        $total_lignes = 0;
        $ignore = false;
        for ($i = 0; $i < count($facture->dol_object->lines); $i++) {
            if ($facture->dol_object->lines[$i]->desc == "Acompte" && $facture->dol_object->lines[$i]->multicurrency_total_ht == $facture->getData('total')) {
                $ignore = true;
//                $force706 = true;
            }
        }
        if ($ignore) {
            $facture->updateField('ignore_compta', 1);
            $facture->updateField('exported', 204);
        }

        $have_product_in_facture = $this->have_in_facture($facture->dol_object->lines);
        $have_service_in_facture = $this->have_in_facture($facture->dol_object->lines, 'service');

        foreach ($facture->dol_object->lines as $line) {
            if (is_null($facture->getData('ignore_compta')) || $facture->getData('ignore_compta') == 0) { // Si la facture n'est pas ignorée en compta
                if (round($line->multicurrency_total_ht, 2) != 0 && !$ignore) {
                    if ($line->fk_product) {
                        $produit = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                        //$type_produit = $produit->getData('fk_product_type');
                        $type_produit = $this->product_or_service($produit->id);
                        $d3e += $produit->getData('deee') * $line->qty;
                    } else {
                        $type_produit = $line->product_type;
                    }

                    $use_compte_general = ($type_produit == 0) ? $compte_general_produit : $compte_general_service;
                    if($force706)
                        $use_compte_general = $compte_general_service;

                    if (!$writing_ligne_client) {
                        $structure['contre_partie'] = [$use_compte_general, 17];
                        $ecritures = $this->struct($structure);
                    }
                    if ($line->fk_product) {
                        $is_frais_de_port = false;
                        $is_commission = false;
                        $is_refact = false;
                        if ($frais_de_port = $this->db->getRow('categorie_product', 'fk_categorie = 9705 AND fk_product = ' . $produit->id) || $produit->id == 129950) {
                            $is_frais_de_port = true;
                            $lignes[$compte_general_port]['HT'] += $line->multicurrency_total_ht;
                            $total_lignes += round($line->multicurrency_total_ht, 2);
                            if ($use_tva && $line->tva_tx != 0) {
                                $lignes[$compte_general_tva]['HT'] += $line->multicurrency_total_tva;
                                $total_lignes += $line->multicurrency_total_tva;
                            }
                        }

//                        $is_remise = false;
//                        $montant_remise = 0;
//                        if($produit->getData('ref') == 'REMISE' || $produit->getData('ref') == 'TEX' || $produit->getData('ref') == 'REMISE-01' || $produit->getData('ref') == 'REMISE-02' || $produit->getData('ref') == 'REMISE-03' || $produit->getData('ref') == 'REMISECRT') {
//                            $is_remise = true;


                        switch ($produit->getData('ref')) {
                            case "REMISE" :
                            case "REMISECRT":
                            case "TEX":
                                $use_compte_general = ($have_product_in_facture) ? $compte_general_produit : $compte_general_service;
                                break;
                            case "REMISE-01":
                            case "REMISE-02":
                            case "REMISE-03":
                                $use_compte_general = ($have_service_in_facture) ? $compte_general_service : $compte_general_produit;
                                break;
                        }
//                            
//                            $lignes[$use_compte_general]['HT'] += $line->multicurrency_total_ht;
//                            $total_lignes += round($line->multicurrency_total_ht, 2);
//                        }

                        if ($produit->getData('ref') == "ZZCOMMISSION") {
                            $is_commission = true;
                            $use_compte_general = $compte_general_comissions;
//                            $lignes[$compte_general_comissions]['HT'] += $line->multicurrency_total_ht;
//                            $total_lignes += round($line->multicurrency_total_ht, 2);
                        }
                        if ($produit->getData('ref') == 'GEN-REFACT-HF') {
                            $use_compte_general = $this->convertion_to_interco_code("70835000", $compte_general_411);
                        }

                        if ($produit->getData('ref') == "GEN-SAV-PIECES") {
                            $use_compte_general = $compte_general_produit;
                        }

                        if ($produit->getData('ref') == "GEN-AUTOFACT") {
                            $use_compte_general = "70704000";
                        }
                        
                        if($produit->getData('ref') == "GEN-AUTOREFACT") {
                            $use_compte_general = "70704000";
                        }
                        
                        if($produit->getData('ref') == 'GEN-AVOIR') {
                            $use_compte_general = "70700000";
                        }
                        
                        if($produit->getData('ref') == "GEN-AVOIR-PRESTATIONS") {
                            $use_compte_general = "70600000";
                        }

                        switch ($produit->getData('ref')) {
                            case "REFACT_FILIALES":
                                $is_refact = true;
                                $lignes[$compte_refact_ht]['HT'] += $line->multicurrency_total_ht;
                                $total_lignes += round($line->multicurrency_total_ht, 2);
                                break;
                            case "REFACT_TTC_FILIALES":
                                $is_refact = true;
                                $lignes[$compte_refact_ttc]['HT'] += $line->multicurrency_total_ht;
                                $total_lignes += round($line->multicurrency_total_ht, 2);
                                break;
                        }

                        if (!$is_frais_de_port && !$is_remise && !$is_refact) {
                            if ($use_d3e) {
                                if (($facture->getData('zone_vente') == 1 && $line->tva_tx != 0) || $facture->getData('zone_vente') != 1) {

                                    $add_ht = $line->multicurrency_total_ht - ($produit->getData('deee') * $line->qty);

                                    if ($line->multicurrency_total_ht < 0) {
                                        $add_ht = $line->multicurrency_total_ht + ($produit->getData('deee') * $line->qty);
                                    }

                                    $lignes[$use_compte_general]['HT'] += $add_ht;
                                    $total_lignes += round($line->multicurrency_total_ht, 2);
                                }
                            } else {
                                if (($facture->getData('zone_vente') == 1 && $line->tva_tx != 0) || $facture->getData('zone_vente') != 1) {
                                    $lignes[$use_compte_general]['HT'] += $line->multicurrency_total_ht;
                                    $total_lignes += round($line->multicurrency_total_ht, 2);
                                }
                            }

                            if ($use_tva && $line->tva_tx != 0) {
                                $lignes[$compte_general_tva]['HT'] += $line->multicurrency_total_tva;
                                $total_lignes += $line->multicurrency_total_tva;
                            } elseif ($use_tva && $line->tva_tx == 0) {
                                $lignes[$compte_general_tva_null]['HT'] += $line->multicurrency_total_ht;
                                $total_lignes += round($line->multicurrency_total_ht, 2);
                            }
                        }
                    } else {
                        if ($use_tva && $line->tva_tx != 0) {
                            $lignes[$compte_general_tva]['HT'] += $line->multicurrency_total_tva;
                            $total_lignes += $line->multicurrency_total_tva;
                            $lignes[$use_compte_general]['HT'] += $line->multicurrency_total_ht;
                            $total_lignes += round($line->multicurrency_total_ht, 2);
                        } elseif ($use_tva && $line->tva_tx == 0) {
                            $lignes[$compte_general_tva_null]['HT'] += $line->multicurrency_total_ht;
                            $total_lignes += round($line->multicurrency_total_ht, 2);
                        }
                    }
                }
            }
        }

        if ($use_d3e && $d3e != 0) {
            $lignes[$compte_general_d3e]['HT'] = $d3e;
        }

        if (round($total_ttc_facture, 2) != round($total_lignes, 2)) {
            $montant_ecart = round($total_ttc_facture, 2) - (round($total_lignes, 2));
            $lignes = $this->rectifications_ecarts($lignes, round($montant_ecart, 2), 'vente');
        }
        foreach ($lignes as $l => $infos) {
            if ($l != 'REMISE') {
                $structure['compte_general'] = [$l, 17];
            } else {
                $structure['compte_general'] = [$info['COMPTE'], 17];
            }

            $structure['type_de_compte'] = [" ", 1];
            $structure['code_auxiliaire'] = ['', 16];
            $structure['montant'] = [abs(round($infos['HT'], 2)), 20, true];
            $structure['sens'] = [$this->get_sens($total_ttc_facture, 'facture', true, $sens_parent), 1];

            $structure['contre_partie'] = [$compte_general_411, 17];
            $structure['vide'] = [$code_auxiliaire, 606];
            $ecritures .= $this->struct($structure);
        }

        return $this->write_tra($ecritures, $file);
    }

    public function export_v2($id_facture, $forced, $confFile)
    {

        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
        $societe = $this->getInstance('bimpcore', 'Bimp_Societe', $facture->getData('fk_soc'));

        if (!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file = $this->create_daily_file('vente', null, $confFile['name'], $confFile['dir']);
        } else {
            $file = $this->create_daily_file('vente', $facture->getData('datef'));
        }

        $is_client_interco = false;
        $is_vente_ticket = false;
        $compte_general_411 = '41100000';
        $total_ttc_facture = $facture->getData('multicurrency_total_ttc');
        $date_facture = new DateTime($facture->getData('datef'));
        $date_creation = new dateTime($facture->getData('datec'));
        $date_echeance = new DateTime($facture->getData('date_lim_reglement'));
        $id_reglement = ($facture->getData('fk_mode_reglement') > 0) ? $facture->getData('fk_mode_reglement') : 6;
        $reglement = $this->db->getRow('c_paiement', 'id = ' . $id_reglement);

        if ($societe->getData('is_subsidiary')) {
            $compte_general_411 = $societe->getData('accounting_account');
            $is_client_interco = true;
        }
        $compte_general_tva_null = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_vente_tva_null'), $compte_general_411);
    }

    public function export_v3($id_facture, $forced, $confFile)
    {

        $errors = [];
        // Définition du nom du fichier
        if (!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file = $this->create_daily_file('vente', null, $confFile['name'], $confFile['dir']);
        } else {
            $file = $this->create_daily_file('vente', $facture->getData('datef'));
        }

        // Définition des différents objets
        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
        $societe = $this->getInstance('bimpcore', 'Bimp_Societe', $facture->getData('fk_soc'));
        $bc_vente = $this->getInstance('bimpcaisse', 'BC_Vente');
        $lines = $facture->dol_object->lines;
//        $have_product_in_facture = $this->have_in_facture($facture->dol_object->lines);
//        $have_service_in_facture = $this->have_in_facture($facture->dol_object->lines, 'service');
        // Définition des différentes variables
        $compte_general_411 = '41100000';
        $inverse = false;
        $writing_ligne_client = false;
        $is_client_interco = false;
        $is_vente_ticket = false;
        $ignore_compta = (is_null($facture->getData('ignore_compta')) || $facture->getData('ignore_compta') == 0) ? false : true;
        $total_ttc_facture = $facture->getData('multicurrency_total_ttc');
        $date_facture = new DateTime($facture->getData('datef'));
        $date_creation = new dateTime($facture->getData('datec'));
        $date_echeance = new DateTime($facture->getData('date_lim_reglement'));
        $id_reglement = ($facture->getData('fk_mode_reglement') > 0) ? $facture->getData('fk_mode_reglement') : 6;
        $reglement = $this->db->getRow('c_paiement', 'id = ' . $id_reglement);
        $label = strtoupper($this->suppr_accents($societe->getData('nom')));
        $d3e = 0;
        $montant_lignes = 0;

        // Vérifi si ue ligne de la facture contien le mot : Acompte et est le total de la facture
        if (!$ignore_compta) {
            for ($i = 0; $i < count($facture->dol_object->lines); $i++) {
                if ($facture->dol_object->lines[$i]->desc == "Acompte" && $facture->dol_object->lines[$i]->multicurrency_total_ht == $facture->getData('total')) {
                    $ignore_compta = true;
                }
            }
            if ($ignore_compta) {
                $facture->updateField('ignore_compta', 1);
                $facture->updateField('exported', 204);
                mailSyn2("BIMPtoCEGID - Facture ignorée en compta", 'dev@bimp.fr', null, 'Bonjour, la facture ' . $facture->getNomUrl(1) . " à été passée en ignorée en compta");
            }
        }

        // Vérifi le sens de la facture (Crédit ou Débit)
        if ($facture->getData('multicurrency_total_ttc') < 0) {
            $inverse = true;
        }
        // Défini le sens de la ligne de la facture correcpondant au client
        $sens_parent = $this->get_sens($facture->getData('multicurrency_total_ttc'), 'facture', $inverse);

        // Vérifi si c'est une vente ticket ou non
        if ($bc_vente->find(['id_facture' => $facture->id])) {
            if ($bc_vente->getData('id_client') == 0) {
                $is_vente_ticket = true;
            }
            $id_entrepot = $bc_vente->getData('id_entrepot'); // Id de l'entrepot est égale à l'id de l'entrepot de la vente 
        } else {
            $id_entrepot = $facture->getData('entrepot');
            //$id_entrepot = BimpCore::getConf('BIMPTOCEGID_default_entrepot'); // Sinon on met l'id de l'entrepot par défaut de la conf du module
        }

        if ($id_entrepot < 1) {
            $errors[] = "Pas d'id entrepot sur la facture";
        }

        if (!count($errors)) {
            $entrepot = $this->db->getRow('entrepot', 'rowid = ' . $id_entrepot);
            if ($is_vente_ticket) {
                $code_auxiliaire = $entrepot->compte_aux;
                $label = strtoupper("vente ticket " . $code_auxiliaire);
            }
            // Définir si le client est un interco ou pas
            if ($societe->getData('is_subsidiary')) {
                $compte_general_411 = $societe->getData('accounting_account');
                $is_client_interco = true;
            }

            // Cherche si le client est déjà exporter en compta et renvois sont code auxiliaire, si jamais le client n'existe aps en compta on le cré
            if ($societe->getData('exported') == 1) {
                $code_auxiliaire = $societe->getData('code_compta');
            } else {
                $export_societe = $this->getInstance('bimptocegid', 'BTC_export_societe');
                $code_auxiliaire = $export_societe->export($societe, 'c', $facture->getData('datef'));
            }
            if (!$ignore_compta) {

                $facture_comptes = [];
                $facture_comptes['facture'][$compte_general_411] = $facture->getData('multicurrency_total_ttc');

                $structure = [
                    'journal'           => [($is_client_interco) ? 'VI' : "VTE", 3],
                    'date'              => [$date_facture->format('dmY'), 8],
                    'type_piece'        => ['FC', 2],
                    'compte_general'    => [$compte_general_411, 17],
                    'type_de_compte'    => ["X", 1],
                    'code_auxiliaire'   => [$code_auxiliaire, 16],
                    'next'              => ['', 1],
                    'ref_interne'       => [$facture->getData('facnumber'), 35],
                    'label'             => [$label, 35],
                    'reglement'         => [($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3],
                    'echeance'          => [$date_echeance->format('dmY'), 8],
                    'sens'              => [$sens_parent, 1],
                    'montant'           => [abs(round($facture_comptes['facture'][$compte_general_411], 2)), 20, true],
                    'type_ecriture'     => [$this->type_ecriture, 1],
                    'numero_piece'      => [$facture->id, 8, true],
                    'devise'            => ['EUR', 3],
                    'taux_dev'          => ['1', 10],
                    'code_montant'      => ['E--', 3],
                    'montant_2'         => ['', 20],
                    'montant_3'         => ['', 20],
                    'etablissement'     => ['001', 3],
                    'axe'               => ['A1', 2],
                    'numero_echeance'   => ['1', 2],
                    'ref_externe'       => [$facture->getData('facnumber'), 35],
                    'date_ref_externe'  => ['01011900', 8],
                    'date_creation'     => [$date_creation->format('dmY'), 8],
                    'societe'           => ['', 3],
                    'affaire'           => ['', 17],
                    'date_taux_dev'     => ['01011900', 8],
                    'nouveau_ecran'     => ['N', 3],
                    'quantite_1'        => ['', 20],
                    'quantite_2'        => ['', 20],
                    'qualif_quantite_1' => ['', 3],
                    'qualif_quantite_2' => ['', 3],
                    'ref_libre'         => ['Export automatique BIMP ERP', 35],
                    'tva_encaissement'  => ['-', 1],
                    'regime_tva'        => ['CEE', 3],
                    'tva'               => ['T', 3],
                    'tpf'               => ['N', 3],
                    'contre_partie'     => ['', 17],
                    'vide'              => ['', 606],
                    'lettrage_dev'      => ['-', 1],
                    'lettrage_euro'     => ['X', 1],
                    'etat_lettrage'     => ['AL', 2],
                    'vide_2'            => ['', 153],
                    'valide'            => ['-', 1],
                    'before'            => ['', 1],
                    'date_debut'        => ['', 8],
                    'date_fin'          => ['', 8]
                ];

                $array_comptes = [];

                foreach ($lines as $line) {
                    if ($line->fk_product && $line->multicurrency_total_ht != 0) {
                        $date_start = null;
                        $date_end = null;
                        $line_with_time = '';
                        $print_date_start = "";
                        $print_date_end = "";
                        $sub_d3e = 0;

                        $stack = 'lines';

                        if ($line->tva_tx == 0 && $facture->getData('zone_vente') == 1) {
                            $stack = 'tva';
                        }

                        if ($line->date_start && $line->date_end) {
                            $line_with_time = "_" . $line->id;
                            // Date de départ de la ligne
                            $date_start = new DateTime();
                            $date_start->setTimestamp($line->date_start);
                            //Date de fin de la ligne
                            $date_end = new DateTime();
                            $date_end->setTimestamp($line->date_end);
                            $print_date_start = $date_start->format('dmY');
                            $print_date_end = $date_end->format('dmY');
                        }

                        $produit = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                        // Définition du compte de vente
                        $compte_vente = $this->convertion_to_interco_code($this->sizing($produit->getCodeComptableVente($facture->getData('zone_vente')), 8, false, true), $compte_general_411) . $line_with_time . '::' . $print_date_start . $print_date_end;
                        // Définition du compte d3e de la facture
                        $compte_d3e = $this->convertion_to_interco_code($this->sizing($produit->getCodeComptableVenteDeee($facture->getData('zone_vente')), 8, false, true), $compte_general_411);
                        // Définition du compte de TVA
                        $compte_tva = $this->sizing($produit->getCodeComptableVenteTva($facture->getData('zone_vente')), 8, false, true);

                        if (!$compte_vente) {
                            $errors[] = "Il n'y à pas eu d'affectation de compte pour le produit de cette facture :" . $produit->getNomUrl();
                        }

                        if ($facture->getData('zone_vente') == 1) {
                            $sub_d3e = $produit->getData('deee') * abs($line->qty);
                        }

                        $array_remise = ["REMISE", "REMISECRT", "TEX", "REMISE-01", "REMISE-02", "REMISE-03"];
                        //$montant_lignes += round(abs($line->multicurrency_total_ht - $sub_d3e), 2);
                        // Remplssage du tableau des lignes pour les produits
                        if (in_array($produit->getData('ref'), $array_remise)) {
                            $facture_comptes['remise_global'] += abs(round($line->multicurrency_total_ht - $sub_d3e, 2));
                        } else {
                            if ($stack == 'lines')
                                $total_lines_ht_for_remise += round($line->multicurrency_total_ht - $sub_d3e, 2);
                            $facture_comptes[$stack][$compte_vente] += abs(round($line->multicurrency_total_ht - $sub_d3e, 2));
                        }

                        if ($sub_d3e != 0) {
                            $facture_comptes['deee'][$compte_d3e] += $sub_d3e;
                            $montant_lignes += $sub_d3e;
                        }

                        if ($facture->getData('multicurrency_total_tva') != 0) {
                            $facture_comptes['tva'][$compte_tva] = abs(round($facture->getData('multicurrency_total_tva'), 2));
                        }
                    }
                }

                if (array_key_exists('remise_global', $facture_comptes)) {
                    //echo "---------- TRAITEMENT DE LA REMISE GLOBAL ----------\n";
                    $nb_lines = count($facture_comptes['lines']);
                    $remise_global = $facture_comptes['remise_global'];
                    $pourcentage = $remise_global * 100 / $total_lines_ht_for_remise;

                    foreach ($facture_comptes['lines'] as $compte => $montant) {
                        $soustraire = ($montant * $pourcentage) / 100;
                        $facture_comptes['lines'][$compte] -= round($soustraire, 2);
                        $montant_lignes += abs(round($facture_comptes['lines'][$compte], 2));
                    }
                } else {
                    foreach ($facture_comptes['lines'] as $compte => $montant) {
                        $montant_lignes += abs(round($montant, 2));
                    }
                }

                foreach ($facture_comptes['tva'] as $compte => $montant) {
                    $montant_lignes += abs(round($montant, 2));
                }

                echo "\n FACTURE : " . abs($facture->getData('multicurrency_total_ttc')) . "\n LIGNES: " . $montant_lignes . "\n";

                $ecart = abs(round($facture->getData('multicurrency_total_ttc') - $montant_lignes, 2));

                if ($ecart > 0) {
                    //$warning[]
                }


                $facture_comptes['lines'] = $this->rectifications_ecarts($facture_comptes['lines'], $ecart, 'vente');

                foreach ($facture_comptes as $categorie => $compte) {

                    if ($categorie != 'facture') {
                        foreach ($compte as $le_compte => $montant) {
                            $final_ctrl += $montant;

                            $explode = explode('_', $le_compte);
                            if (!$writing_ligne_client) {
                                $structure['contre_partie'] = [str_replace('::', '', $explode[0]), 17];
                                $ecriture = $this->struct($structure);
                                $writing_ligne_client = true;
                            }
                            $hasDate = explode('::', $le_compte);

                            if ($hasDate[1]) {
                                $print_date_start = substr($hasDate[1], 0, 8);
                                $print_date_end = substr($hasDate[1], 0, 16);
                            }

                            $structure['compte_general'] = [str_replace('::', '', $explode[0]), 17];
                            $structure['type_de_compte'] = [" ", 1];
                            $structure['code_auxiliaire'] = ["", 16];
                            $structure['contre_partie'] = [$compte_general_411, 17];
                            $structure['sens'] = [$this->get_sens($montant, 'facture', true, $sens_parent), 1];
                            $structure['montant'] = [abs(round($montant, 2)), 20, true];
                            $structure['vide'] = [$code_auxiliaire, 606];



                            $structure['date_debut'] = [$print_date_start, 8];
                            $structure['date_fin'] = [$print_date_end, 8];

                            $print_date_start = "";
                            $print_date_end = "";

                            $ecriture .= $this->struct($structure);
                        }
                    }
                }
                if (abs(round($facture->getData('multicurrency_total_ttc'), 2)) != $final_ctrl) {
                    $errors[] = "La facture n'à pas été exporter en compta car l'écart n'a pas pu etre récupérer par le module. TOTAL FACTURE = " . abs(round($facture->getData('multicurrency_total_ttc'), 2)) . ", MONTANT DES LIGNES = " . $final_ctrl;
                }

                echo $ecriture;
                echo '</pre>';
            } else {
                echo "Cette facture est ignoré en compta";
            }
        }

        if (count($errors)) {
            mailSyn2("Erreur EXPORT CEGID", "dev@bimp.fr", null, "Facture :" . $facture->getNomUrl() . print_r($errors, 1));
        } else {
            
        }
    }
}
