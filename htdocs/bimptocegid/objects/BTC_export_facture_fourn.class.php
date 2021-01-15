<?php

class BTC_export_facture_fourn extends BTC_export {

    const CAUSE_ECART = 1;
    const CAUSE_TVA = 2;
    const CAUSE_ZONE_ACHAT = 3;

    public static $avoir_fournisseur = [];
    public static $rfa_fournisseur = ['GEN-CRT', 'GEN-RFA', 'GEN-IPH', 'REMISE', 'GEN-RETROCESSION', 'GEN-AVOIR', 'GEN-AVOIR-6097000', "GEN-PUB", "GEN-INCENTIVE", "GEN-PROTECTPRIX", "GEN-REBATE"];

    public function export($id_facture, $forced, $confFile) {

        $facture = $this->getInstance('bimpcommercial', 'Bimp_FactureFourn', $id_facture);
        $datec = new DateTime($facture->getData('datec'));
        
        if (!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file = $this->create_daily_file('achat', null, $confFile['name'], $confFile['dir']);
        } else {
            $file = $this->create_daily_file('achat', $datec->format("Y-m-d"));
        }

        $societe = $this->getInstance('bimpcore', 'Bimp_Societe', $facture->getData('fk_soc'));
        $is_fournisseur_interco = false;

        $compte_general_401 = '40100000';

        if ($societe->getData('is_subsidiary')) {
            $compte_general_401 = $societe->getData('accounting_account_fournisseur');
            $is_fournisseur_interco = true;
        }
        if ($societe->getData('exported') == 1) {
            $code_auxiliaire = $societe->getData('code_compta_fournisseur');
        } else {
            $export_societe = $this->getInstance('bimptocegid', 'BTC_export_societe');
            $code_auxiliaire = $export_societe->export($societe, "f", $datec->format("Y-m-d"));
        }


        if ($facture->getData('date_lim_reglement')) {
            $date_echeance = new DateTime($facture->getData('date_lim_reglement'));
        } else {
            $cond = $facture->getData('fk_cond_reglement');
            $date_echeance = new DateTime($facture->getData('datef'));
            if ($cond == 48) {
                $date_echeance->add(new DateInterval("P60D"));
            } elseif ($conf == 52) {
                $date_echeance->add(new DateInterval("P30D"));
            } elseif ($cond == 7) {
                $date_echeance->add(new DateInterval("P45D"));
            }
            $date_echeance->add(new DateInterval("P1D"));
        }

        $type_facture = $facture->getData('type'); // 0 -> Facture standard, 2 -> Facture avoir, 3 -> Facture accompte
        switch ($type_facture) {
            case 2:
                $type_piece = 'AF';
                break;
            case 3:
                $type_piece = 'OF';
                break;
            default:
                $type_piece = 'FF';
                break;
        }
        $date_facture = new DateTime($facture->getData('datef'));
        $date_creation = new DateTime($facture->getData('datec'));
        $reglement = $this->db->getRow('c_paiement', 'id = ' . $facture->getData('fk_mode_reglement'));

        $liste_des_lignes_facture = $facture->dol_object->lines;
        $lignes = [];
        $total_ttc_facture = $facture->getData('total_ttc');
        $inverse = false;
        if ($total_ttc_facture < 0) {
            $inverse = true;
        }

        $zone_achat = $facture->getData('zone_vente');
        switch ($zone_achat) {
            case 2: // Achat en UE
                $use_tva = false;
                $use_d3e = false;
                $use_autoliquidation = true;
                $compte_achat_produit = BimpCore::getConf('BIMPTOCEGID_achat_produit_ue');
                $compte_achat_service = BimpCore::getConf('BIMPTOCEGID_achat_service_ue');
                $compte_frais_de_port = BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_ue');
                $compte_achat_deee = null;
                $compte_achat_tva_null = null;
                $compte_achat_tva_null_service = null;
                $compte_achat_tva = null;
                // METTRE LES COMPTES RFA ICI
                break;
            case 4: // Achat en UE
                $use_tva = false;
                $use_d3e = false;
                $use_autoliquidation = true;
                $compte_achat_produit = BimpCore::getConf('BIMPTOCEGID_achat_produit_ue');
                $compte_achat_service = BimpCore::getConf('BIMPTOCEGID_achat_service_ue');
                $compte_frais_de_port = BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_ue');
                $compte_achat_deee = null;
                $compte_achat_tva_null = null;
                $compte_achat_tva_null_service = null;
                $compte_achat_tva = null;
                break;
            case 3: // Achat export
                $use_tva = false;
                $use_d3e = false;
                $use_autoliquidation = false;
                $compte_achat_produit = BimpCore::getConf('BIMPTOCEGID_achat_produit_ex');
                $compte_achat_service = BimpCore::getConf('BIMPTOCEGID_achat_service_ex');
                $compte_frais_de_port = BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_ex');
                $compte_achat_deee = null;
                $compte_achat_tva_null = null;
                $compte_achat_tva_null_service = null;
                $compte_achat_tva = null;
                break;
            default:
                $use_d3e = true;
                $use_tva = true;
                $use_autoliquidation = false;
                $compte_achat_produit = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_achat_produit_fr'), $compte_general_401);
                $compte_achat_service = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_achat_service_fr'), $compte_general_401);
                $compte_frais_de_port = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_frais_de_port_achat_fr'), $compte_general_401);
                $compte_achat_tva_null = BimpCore::getConf('BIMPTOCEGID_achat_tva_null');
                $compte_achat_tva_null_service = BimpCore::getConf('BIMPTOCEGID_achat_tva_null_service');
                $compte_achat_tva = BimpCore::getConf('BIMPTOCEGID_achat_tva_fr');
                $compte_achat_deee = $this->convertion_to_interco_code(BimpCore::getConf('BIMPTOCEGID_achat_dee_fr'), $compte_general_401);
                break;
        }

        $sens_parent = $this->get_sens($total_ttc_facture, 'facture_fourn', $inverse);
        $structure = [
            'journal' => [($is_fournisseur_interco) ? 'AI' : 'ACM', 3],
            'date' => [$date_facture->format('dmY'), 8],
            'type_piece' => [$type_piece, 2],
            'compte_general' => [$compte_general_401, 17],
            'type_de_compte' => ["X", 1],
            'code_auxiliaire' => [$code_auxiliaire, 16],
            'next' => ['', 1],
            'ref_interne' => [$facture->getData('ref'), 35],
            'label' => [strtoupper($this->suppr_accents($societe->getData('nom'))), 35],
            'reglement' => [($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3],
            'echeance' => [$date_echeance->format('dmY'), 8],
            'sens' => [$this->get_sens($total_ttc_facture, 'facture_fourn', $inverse), 1],
            'montant' => [abs(round($total_ttc_facture, 2)), 20, true],
            'type_ecriture' => ['S', 1],
            'numero_piece' => [$facture->id, 8, true],
            'devise' => ['EUR', 3],
            'taux_dev' => ['1', 10],
            'code_montant' => ['E--', 3],
            'montant_2' => ['', 20],
            'montant_3' => ['', 20],
            'etablissement' => ['001', 3],
            'axe' => ['A1', 2],
            'numero_echeance' => ['1', 2],
            'ref_fournisseur' => [$facture->getData('ref_supplier'), 35],
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
        $writing_ligne_fournisseur = false;
        $total_lignes_facture = 0;
        foreach ($liste_des_lignes_facture as $ligne) {
            if ($ligne->total_ttc != doubleval(0)) {
                $total_lignes_facture += round($ligne->total_ht, 2);
                if ($ligne->fk_product) {
                    $is_rfa = false;
                    $produit = $this->getInstance('bimpcore', 'Bimp_Product', $ligne->fk_product);
                    //$frais_de_port = $this->db->getRow('categorie_product', 'fk_categorie = 9705 AND fk_product = ' . $produit->id);

                    if ($frais_de_port = $this->db->getRow('categorie_product', 'fk_categorie = 9705 AND fk_product = ' . $produit->id) || $produit->id == 129950) { // ID du produit à enlever quand il sera categoriser (FRAIS DE PORT LDLC
                        if ($use_tva && $ligne->tva_tx == 0) {
                            $use_compte_general = $this->convertion_to_interco_code($compte_achat_tva_null_service, $compte_general_401);
                        } else {
                            $use_compte_general = $this->convertion_to_interco_code($compte_frais_de_port, $compte_general_401);
                        }
                        //$lignes[$use_compte_general]['HT'] += $ligne->total_ht;
                    } else {
                        $use_compte_general = $this->convertion_to_interco_code($produit->getCodeComptableAchat($facture->getData('zone_vente'), -1, $ligne->tva_tx), $compte_general_401);
                        //$use_compte_general = $produit->getCodeComptableAchat($facture->getData('zone_vente'));
                        if ($this->isApple($societe->getData('code_compta_fournisseur'))) {
                            $use_compte_general = BimpCore::getConf('BIMPTOCEGID_achat_fournisseur_apple');
                        }
                    }

                    if (in_array($produit->getData('ref'), self::$rfa_fournisseur)) {
                        $is_rfa = true;
                        switch ($zone_achat) {
                            case 1:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_rfa_fournisseur_fr');
                                break;
                            case 2:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_rfa_fournisseur_ue');
                                break;
                            case 3:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_rfa_fournisseur_ex');
                                break;
                            case 4:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_rfa_fournisseur_ue');
                                break;
                        }
                    } elseif (in_array($produit->getData('ref'), self::$avoir_fournisseur)) {
                        switch ($zone_achat) {
                            case 1:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_avoir_fournisseur_fr');
                                break;
                            case 2:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_avoir_fournisseur_ue');
                                break;
                            case 3:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_avoir_fournisseur_ex');
                                break;
                            case 4:
                                $use_compte_general = BimpCore::getConf('BIMPTOCEGID_avoir_fournisseur_ue');
                                break;
                        }

                        if ($this->isApple($societe->getData('code_compta_fournisseur'))) {
                            $use_compte_general = BimpCore::getConf('BIMPTOCEGID_avoir_fournisseur_apple'); // On applique le compte comptable des avoirs chez APPLE
                        }
                    }

                    $deeee = $produit->getData('deee');
                } else {
                    $use_compte_general = $compte_achat_service;
                    $deeee = 0;
                }

                // Commancer a ecrire dans le tableau des lignes 

                if ($use_d3e && $deeee > 0) {
                    $lignes[$use_compte_general]['HT'] += $ligne->total_ht - ($deeee * $ligne->qty);
                    $lignes[$compte_achat_deee]['HT'] += $deeee * $ligne->qty;
                } else {
                    $lignes[$use_compte_general]['HT'] += $ligne->total_ht;
                }

                
                
                $contre_partie_ligne_fournisseur = $use_compte_general;
//                    if ($use_d3e && !$is_rfa) {
//                        if ($produit->isLoaded()) {
//                            $use_compte_general = $compte_achat_deee;
//                            if ($produit->getData('deee') > 0) {
//                                
//                            }
//                        }
//                    }
            }


            if ($use_tva && $ligne->tva_tx > 0) {
                $use_compte_general = $compte_achat_tva;
                $lignes[$use_compte_general]['HT'] += $ligne->total_tva;
            }

            if (!$writing_ligne_fournisseur) {
                $structure['contre_partie'] = [$contre_partie_ligne_fournisseur, 17];
                $ecritures = $this->struct($structure);
                $writing_ligne_fournisseur = true;
            }
        }



        if (round($total_ttc_facture, 2) != round($total_lignes_facture, 2)) {
            $montant_ecart = round($total_ttc_facture, 2) - round($total_lignes_facture, 2);
            $this->rectifications_ecarts($lignes, round($montant_ecart, 2), 'achat');
        }

        foreach ($lignes as $l => $infos) {
            $structure['compte_general'] = [$l, 17];
            $structure['type_de_compte'] = ['-', 1];
            $structure['code_auxiliaire'] = ['', 16];
            $structure['sens'] = [$this->get_sens($infos['HT'], 'facture_fourn', false, $sens_parent), 1];
            $structure['vide'] = [$code_auxiliaire, 606];
            $structure['montant'] = [abs(round($infos['HT'], 2)), 20, true];
            $structure['contre_partie'] = [$compte_general_401, 17];
            $ecritures .= $this->struct($structure);
        }

        // Entre en jeu que si l'autoliquidation de TVA est activé et que la societé à un numéro de TVA intracommunaitaire
        if ($use_autoliquidation && $societe->getData('tva_intra')) {
            $tva_calcule = round(20 * $total_ttc_facture / 100, 2);
            $structure['compte_general'] = [BimpCore::getConf('BIMPTOCEGID_autoliquidation_tva_666'), 17];
            $structure['sens'] = [$this->get_sens($total_ttc_facture, 'facture_fourn', false, $sens_parent), 1];
            $structure['montant'] = [abs(round($tva_calcule, 2)), 20, true];
            $ecritures .= $this->struct($structure);
            $structure['compte_general'] = [BimpCore::getConf('BIMPTOCEGID_autoliquidation_tva_711'), 17];
            $structure['sens'] = [$sens_parent, 1];
            $ecritures .= $this->struct($structure);
        }

        return $this->write_tra($ecritures, $file);
    }

}
