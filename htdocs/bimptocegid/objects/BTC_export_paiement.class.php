<?php

class BTC_export_paiement extends BTC_export {

    const RETURNED_STATUS_NO = 0;
    const RETURNED_STATUS_OK = 1;

    public function export($id, $paiement_id, $forced, $confFile) {
        
        $error = 0;
        $ecritures = '';
        $liste_transactions = $this->db->getRows('paiement_facture', 'fk_paiement = ' . $id);
        $paiement = $this->getInstance('bimpcommercial', 'Bimp_Paiement', $id);
        $datec = new DateTime($paiement->getData('datec'));

        if(!empty($confFile['name']) && !empty($confFile['dir'])) {
            $file =$this->create_daily_file('paiement', null, $confFile['name'], $confFile['dir']);
        } else {
            $file =$this->create_daily_file('paiement', $datec->format("Y-m-d"));
        }
        
        //$file = $this->create_daily_file('paiement', $datec->format("Y-m-d"));
        foreach ($liste_transactions as $transaction) {
            $is_vente_ticket = false;
            $is_client_interco = false;
            $compte_general_411 = '41100000';

            $facture = $this->getInstance('bimpcommercial', 'Bimp_Facture', $transaction->fk_facture);
                $bc_paiement = $this->getInstance('bimpcaisse', 'BC_Paiement');

                if ($bc_paiement->find(['id_paiement' => $paiement->id])) {
                    if ($bc_paiement->getData('id_vente') > 0) {
                        $vente = $this->getInstance('bimpcaisse', 'BC_Vente', $bc_paiement->getData('id_vente'));
                        $id_entrepot = $vente->getData('id_entrepot');
                        if ($vente->getData('id_client') == 0) {
                            $is_vente_ticket = true;
                        } else {
                            $id_client = $vente->getData('id_client'); 
                        }
                    } else {
                        $caisse = $this->getInstance('bimpcaisse', 'BC_Caisse', $bc_paiement->getData('id_caisse'));
                        $id_entrepot = $caisse->getData('id_entrepot');
                        $id_client = $facture->getData('fk_soc');
                    }
                } else {
                    $id_entrepot = $facture->getData('entrepot');
                    $id_client = $facture->getData('fk_soc');
                }

                $entrepot = $this->loadEntrepot($id_entrepot);
                $reglement = $this->db->getRow('c_paiement', 'id = ' . $paiement_id);

                if (!$is_vente_ticket) {
                    $client = $this->getInstance('bimpcore', 'Bimp_Client', $id_client);
                    if ($client->getData('exported') === 0) {
                        $export_societe = $this->getInstance('bimptocegid', 'BTC_export_societe');
                        $auxiliaire_client = $export_societe->export($client, 'c', $datec->format("Y-m-d"));
                    } else {
                        $auxiliaire_client = $client->getData('code_compta');
                    }

                    if ($client->getData('is_subsidiary')) {
                        $compte_general_411 = $client->getData('accounting_account');
                        $is_client_interco = true;
                    }
                } else {
                    $auxiliaire_client = $entrepot->compte_aux;
                }
                $fk_bank = $paiement->getData('fk_bank');
                $compte_num = $this->db->getValue('bank', 'fk_account', 'rowid = ' . $fk_bank);
                $compte_bancaire = $this->db->getRow('bank_account', 'rowid = ' . $compte_num);

                $affiche_code_reglement = $reglement->code;
                $date = new DateTime($paiement->getData('datep'));
                $label = ($transaction->amount > 0) ? "Pay" : "Rem";
                $label .= ' clt ' . $auxiliaire_client . ' ' . $date->format('dmY');
                
                $compte_g = $entrepot->compte_comptable;
                $journal = $entrepot->code_journal_compta;
                $affiche_code_reglement = $reglement->code;
                
                switch ($reglement->code) {

                    case 'LIQ':
                        $compte_g = $entrepot->compte_comptable;
                        $journal = $entrepot->code_journal_compta;
                        $affiche_code_reglement = "ESP";
                        break;

                    case "CHQ":
                        $compte_g = $entrepot->compte_comptable_banque;
                        $journal = $entrepot->code_journal_compta;
                        break;

                    case "CB":
                        $compte_g = $entrepot->compte_comptable_banque;
                        $journal = $entrepot->code_journal_compta;
                        break;

                    case "AE":
                        $compte_g = $entrepot->compte_comptable_banque;
                        $journal = $entrepot->code_journal_compta;
                        break;

                    case "VIR":
                        $compte_g = $compte_bancaire->compte_compta;
                        $journal = $compte_bancaire->cegid_journal;
                        break;

                    case "PRE":
                        $compte_g = $compte_bancaire->compte_compta;
                        $journal = $compte_bancaire->cegid_journal;
                        break;

                    case "PRELEV":
                        $compte_g = $compte_bancaire->compte_compta;
                        $journal = $compte_bancaire->cegid_journal;
                        break;

                    case "FIN":
                        $compte_g = "41199000";
                        $journal = "OD";
                        break;

                    case "SOFINC":
                        $compte_g = "41199100";
                        $journal = "OD";
                        $affiche_code_reglement = "FIN";
                        break;
                    case "CG":
                        $compte_g = "41199200";
                        $journal = "OD";
                        $affiche_code_reglement = 'CHQ';
                        $label = "Pay clt CG " . $entrepot->town;
                        break;
                    case "FIN_YC":
                        $compte_g  = "51151200";
                        $affiche_code_reglement = 'YOU';
                        break;
                }

                
                $numero_unique = preg_replace('~\D~', '', $paiement->getData('ref'));
                $numero_unique = substr($numero_unique, 1, 8);
                                
                $structure = [
                    'journal' => [$journal, 3],
                    'date' => [$date->format('dmY'), 8],
                    'type_piece' => ['RC', 2],
                    'compte' => [$compte_g, 17],
                    'type_compte' => ["X", 1],
                    'code_compta' => ['', 16],
                    'next' => ['', 1],
                    'ref' => [$paiement->getData('ref'), 35],
                    'label' => [$label, 35],
                    'mode_reglement' => [$affiche_code_reglement, 3, true],
                    'date_reglement' => [$date->format('dmY'), 8],
                    'sens' => [$this->get_sens($transaction->amount, 'paiement'), 1],
                    'montant' => [abs(round($transaction->amount, 2)), 20, true],
                    'type_ecriture' => ["N", 1],
                    'numero_piece' => [$numero_unique, 8, true],
                    'devise' => ['EUR', 3],
                    'taux_dev' => ['1', 10, true],
                    'code_montant' => ['E--', 3],
                    'montant_2' => ['', 20],
                    'montan_3' => ['', 20],
                    'etablissement' => ['001', 3],
                    'axe' => ['', 2],
                    'num_echeance' => ['0', 2],
                    'facture' => [$facture->getData('facnumber'), 35],
                    'date_reference_externe' => ['01011900', 8],
                    'date_creation' => [$date->format('dmY'), 8],
                    'societe' => ['', 3],
                    'affaire' => ['', 17],
                    'date_taux_dev' => ['01011900', 8],
                    'nouveau_ecran' => ['N', 3],
                    'quantite_1' => ['', 20],
                    'quantite_2' => ['', 20],
                    'quanlite_qualitative_1' => ['', 3],
                    'quantite_qualitative_2' => ['', 3],
                    'reference_libre' => ['Export automatique BIMP ERP', 35],
                    'regime_tva' => ['-FRA', 4],
                    'tva' => ['', 3],
                    'tpf' => ['', 3],
                    'contre_partie' => [$compte_general_411, 17],
                    'vide' => [substr($auxiliaire_client, 0, 16), 34],
                    'date_1' => ['01011900', 8],
                    'date_2' => ['01011900', 8],
                    'date_3' => ['01011900', 8],
                    'vide_2' => ['', 454],
                    'date_4' => ['01011900', 8],
                    'vide_3' => ['', 65],
                    'date_5' => ['01011900', 8],
                    'date_6' => ['01011900', 8],
                    'vide_4' => ['', 5],
                    'lettrage' => ['-XRI', 4],
                    'vide_5' => ['', 154],
                    'etat' => ['-', 1],
                    'vide_6' => ['', 59],
                    'fin' => ['0', 2]
                ];

                $ecritures .= $this->struct($structure);

                $structure['compte'] = [$compte_general_411, 17];
                $structure['code_compta'] = [$auxiliaire_client, 16];
                $structure['sens'] = [$this->get_sens($transaction->amount, 'paiement', true), 1];
                $structure['contre_partie'] = [$compte_g, 17];
                $structure['vide'] = ['', 34];
                $structure['lettrage'] = ['-XAL', 4];
                $ecritures .= $this->struct($structure);

        }
        
        return $this->write_tra($ecritures, $file);
    }

}
