<?php

class BTC_export_facture extends BTC_export {
    
    public function export($id_facture, $forced, $confFile) {
        
        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
        
        if(!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file =$this->create_daily_file('vente', null, $confFile['name'], $confFile['dir']);
        } else {
            $file =$this->create_daily_file('vente', $facture->getData('datef'));
        }
        
        //$file = $this->create_daily_file('vente', $facture->getData('datef'));
        if($contact = $this->db->getRow('element_contact', 'element_id = ' . $facture->getData('fk_soc') . ' AND fk_c_type_contact = 60')) {
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
        
        $compte_general_tva_null = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_vente_tva_null'), $compte_general_411);
        if ($societe->getData('is_subsidiary')) {
            $compte_general_411 = $societe->getData('accounting_account');
            $is_client_interco = true;
        }
        
        $compte_refact_ht = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_refacturation_ht'), $compte_general_411);
        $compte_refact_ttc = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_refacturation_ttc'), $compte_general_411);;
        
        switch($facture->getData('zone_vente')) {
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
        
        if($total_ttc_facture < 0) {
            $inverse = true;
        }
        $sens_parent = $this->get_sens($total_ttc_facture, 'facture', $inverse);
        
        if($societe->getData('exported') == 1) {
            $code_auxiliaire = $societe->getData('code_compta');
        } else {
            $export_societe = $this->getInstance('bimptocegid', 'BTC_export_societe');
            $code_auxiliaire = $export_societe->export($societe, 'c', $facture->getData('datef'));
        }
        
        $label = strtoupper($this->suppr_accents($societe->getData('nom')));
        $bc_vente = $this->getInstance('bimpcaisse', 'BC_Vente');
        if($bc_vente->find(['id_facture' => $facture->id])) {
            if($bc_vente->getData('id_client') == 0) {
                $is_vente_ticket = true;                
            }
            $id_entrepot = $bc_vente->getData('id_entrepot');
        } else {
            $id_entrepot = 50;
        }
        $entrepot = $this->db->getRow('entrepot', 'rowid = ' . $id_entrepot);
        if($is_vente_ticket) {
            $code_auxiliaire = $entrepot->compte_aux;
            $label = strtoupper("vente ticket " . $code_auxiliaire);
        }
        
        
        $structure = [
            'journal' => [($is_client_interco) ? 'VI' : "VTE", 3],
            'date' => [$date_facture->format('dmY'), 8],
            'type_piece' => ['FC', 2],
            'compte_general' => [$compte_general_411, 17],
            'type_de_compte' => ["X", 1],
            'code_auxiliaire' => [$code_auxiliaire, 16],
            'next' => ['', 1],
            'ref_interne' => [$facture->getData('facnumber'), 35],
            'label' => [$label, 35],
            'reglement' => [($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3],
            'echeance' => [$date_echeance->format('dmY'), 8],
            'sens' => [$sens_parent, 1],
            'montant' => [abs(round($total_ttc_facture, 2)), 20, true], 
            'type_ecriture' => [$this->type_ecriture, 1],
            'numero_piece' => [$facture->id, 8, true],
            'devise' => ['EUR', 3],
            'taux_dev' => ['1', 10],
            'code_montant' => ['E--', 3],
            'montant_2' => ['', 20],
            'montant_3' => ['', 20],
            'etablissement' => ['001', 3],
            'axe' => ['A1', 2],
            'numero_echeance' => ['1', 2],
            'ref_externe' => [$facture->getData('facnumber'), 35],
            'date_ref_externe' => ['01011900', 8],
            'date_creation' => [$date_creation->format('dmY'), 8],
            'societe' => ['', 3],
            'affaire' => ['', 17],
            'date_taux_dev' => ['01011900', 8],
            'nouveau_ecran' => ['N', 3],
            'quantite_1' => ['', 20],
            'quantite_2' => ['', 20],
            'qualif_quantite_1' => ['', 3],
            'qualif_quantite_2' => ['', 3],
            'ref_libre' => ['Export automatique BIMP ERP', 35],
            'tva_encaissement' => ['-', 1],
            'regime_tva' => ['CEE', 3],
            'tva' => ['T', 3],
            'tpf' => ['N', 3],
            'contre_partie' => ['', 17],
            'vide' => ['', 606],
            'lettrage_dev' => ['-', 1],
            'lettrage_euro' => ['X', 1],
            'etat_lettrage' => ['AL', 2],
            'vide_2' => ['', 153],
            'valide' => ['-', 1],
            'before' => ['', 1],
            'date_debut' => ['', 8],
            'date_fin' => ['', 8]
        ];
        
        $writing_ligne_client = false;
        $total_lignes_facture = 0;
        $d3e  = 0;
        $lignes = [];
        $total_lignes = 0;
        $ignore = false;
        for ($i = 0; $i < count($facture->dol_object->lines); $i++) {
            if ($facture->dol_object->lines[$i]->desc == "Acompte" && $facture->dol_object->lines[$i]->multicurrency_total_ht == $facture->getData('total')) {
                $ignore = true;
            } 
        }
        if($ignore) {
            $facture->updateField('ignore_compta', 1);
            $facture->updateField('exported', 204);
        }
        
        $have_product_in_facture = $this->have_in_facture($facture->dol_object->lines);
        $have_service_in_facture = $this->have_in_facture($facture->dol_object->lines, 'service');
        
        foreach($facture->dol_object->lines as $line) {
            if(is_null($facture->getData('ignore_compta')) || $facture->getData('ignore_compta') == 0) { // Si la facture n'est pas ignorée en compta
                if(round($line->multicurrency_total_ht, 2) != 0 && !$ignore) {
                    if($line->fk_product) {
                        $produit = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
                        //$type_produit = $produit->getData('fk_product_type');
                        $type_produit = $this->product_or_service($produit->id);
                        $d3e += $produit->getData('deee') * $line->qty;
                    } else {
                        $type_produit = $line->product_type;
                    }
                    
                    $use_compte_general = ($type_produit == 0) ? $compte_general_produit : $compte_general_service;           
                    
                    if(!$writing_ligne_client) {
                        $structure['contre_partie'] = [$use_compte_general, 17];
                        $ecritures = $this->struct($structure);
                    }
                    if($line->fk_product){
                        $is_frais_de_port = false;
                        $is_commission = false;
                        $is_refact = false;
                        if($frais_de_port = $this->db->getRow('categorie_product', 'fk_categorie = 9705 AND fk_product = ' . $produit->id) || $produit->id == 129950) {
                            $is_frais_de_port = true;
                            $lignes[$compte_general_port]['HT'] += $line->multicurrency_total_ht;
                            $total_lignes += round($line->multicurrency_total_ht, 2);
                            if($use_tva && $line->tva_tx != 0) {
                                $lignes[$compte_general_tva]['HT'] += $line->multicurrency_total_tva;
                                $total_lignes += $line->multicurrency_total_tva;
                            }
                        }
                        
//                        $is_remise = false;
//                        $montant_remise = 0;
//                        if($produit->getData('ref') == 'REMISE' || $produit->getData('ref') == 'TEX' || $produit->getData('ref') == 'REMISE-01' || $produit->getData('ref') == 'REMISE-02' || $produit->getData('ref') == 'REMISE-03' || $produit->getData('ref') == 'REMISECRT') {
//                            $is_remise = true;
                            
                        
                            switch($produit->getData('ref')) {
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
                        
                        if($produit->getData('ref') == "ZZCOMMISSION") {
                            $is_commission = true;
                            $lignes[$compte_general_comissions]['HT'] += $line->multicurrency_total_ht;
                            $total_lignes += round($line->multicurrency_total_ht, 2);
                        }
                        
                        switch($produit->getData('ref')) {
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
                        
                        if(!$is_frais_de_port && !$is_remise && !$is_commission && !$is_refact) {
                            if($use_d3e){
                                if(($facture->getData('zone_vente') == 1 && $line->tva_tx != 0) || $facture->getData('zone_vente') != 1){
                                    
                                    $add_ht = $line->multicurrency_total_ht - ($produit->getData('deee') * $line->qty);
                                    
                                    if($line->multicurrency_total_ht < 0) {
                                        $add_ht = $line->multicurrency_total_ht + ($produit->getData('deee') * $line->qty);
                                    }
                                    
                                    $lignes[$use_compte_general]['HT'] += $add_ht;
                                    $total_lignes += round($line->multicurrency_total_ht, 2);
                                }
                            } else {
                                if(($facture->getData('zone_vente') == 1 && $line->tva_tx != 0) || $facture->getData('zone_vente') != 1){
                                    $lignes[$use_compte_general]['HT'] += $line->multicurrency_total_ht;
                                   $total_lignes += round($line->multicurrency_total_ht, 2);
                                }
                            }
                            
                            if($use_tva && $line->tva_tx != 0) {
                                $lignes[$compte_general_tva]['HT'] += $line->multicurrency_total_tva;
                                $total_lignes += $line->multicurrency_total_tva;
                            } elseif($use_tva && $line->tva_tx == 0) {
                                $lignes[$compte_general_tva_null]['HT'] += $line->multicurrency_total_ht;
                                $total_lignes += round($line->multicurrency_total_ht, 2);
                            }
                            
                        }
                    } else {
                        if($use_tva && $line->tva_tx != 0) {
                                $lignes[$compte_general_tva]['HT'] += $line->multicurrency_total_tva;
                                $total_lignes += $line->multicurrency_total_tva;
                                $lignes[$use_compte_general]['HT'] += $line->multicurrency_total_ht;
                                $total_lignes += round($line->multicurrency_total_ht, 2);
                            } elseif($use_tva && $line->tva_tx == 0) {
                                $lignes[$compte_general_tva_null]['HT'] += $line->multicurrency_total_ht;
                                $total_lignes += round($line->multicurrency_total_ht, 2);
                            }
                    }
                }
            } 
        }
        
        if($use_d3e && $d3e != 0) {
            $lignes[$compte_general_d3e]['HT'] = $d3e;
        }

        if(round($total_ttc_facture, 2) != round($total_lignes, 2)) {            
            $montant_ecart = round($total_ttc_facture, 2) - (round($total_lignes, 2));
            $lignes = $this->rectifications_ecarts($lignes, round($montant_ecart,2), 'vente');
           
        }
        foreach($lignes as $l => $infos) {
            if($l != 'REMISE') {
                $structure['compte_general'] = [$l, 17];
            } else {
                $structure['compte_general'] = [$info['COMPTE'], 17];
            }
            
            $structure['type_de_compte'] = ['-', 1];
            $structure['code_auxiliaire'] = ['', 16];
            $structure['montant'] = [abs(round($infos['HT'], 2)), 20, true];
                $structure['sens'] = [$this->get_sens($total_ttc_facture, 'facture', true, $sens_parent), 1];

            $structure['contre_partie'] = [$compte_general_411, 17];
            $structure['vide'] = [$code_auxiliaire, 606];
            $ecritures .= $this->struct($structure);
        }
        
        return $this->write_tra($ecritures, $file);
        
    }
    
    public function export_v2($id_facture, $forced, $confFile) {
        
        $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $id_facture);
        $societe = $this->getInstance('bimpcore', 'Bimp_Societe', $facture->getData('fk_soc'));
        
        if(!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file =$this->create_daily_file('vente', null, $confFile['name'], $confFile['dir']);
        } else {
            $file =$this->create_daily_file('vente', $facture->getData('datef'));
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

}

