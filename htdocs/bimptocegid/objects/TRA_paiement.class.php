<?php
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/code_journal.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_tiers.class.php';
    
    class TRA_paiement {
        
        protected $db;
        protected $compte_general;
        public $rapport = [];
        private $caisse;
        private $TRA_tiers;
        public $rapportTier = [];
        protected $compteCheque = '51124000';//ACTIMAC 51124000
        protected $codeJournalCheque = 'REC';//ACTIMAC 51124000
        
        
        function __construct($bimp_db, $tiers_file) { 
            $this->db = $bimp_db; 
            $this->TRA_tiers = new TRA_tiers($bimp_db, $tiers_file);
        }
        
        public function constructTra($transaction, Bimp_Paiement $parent_paiement, $pay) {
            
            $facture        = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $transaction->fk_facture);
            $client         = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', /*(!$this->isVenteTicket($parent_paiement->id) ? $this->vente->getData('id_client') :*/ $facture->getData('fk_soc')/*)*/);
            $datec          = new DateTime($parent_paiement->getData('datec'));
            $datep          = new DateTime($parent_paiement->getData('datep'));
            $entrepot       = $this->db->getRow('entrepot', 'rowid = ' . ($this->isVenteTicket($parent_paiement->id) ? $this->caisse->getData('id_entrepot') : $facture->getData('entrepot')));
            $code_compta    = ($this->isVenteTicket($parent_paiement->id)) ? $entrepot->compte_aux : $this->TRA_tiers->getCodeComptable($client);
            $compte_bancaire = $this->db->getRow('bank_account', 'rowid = ' . $this->db->getValue('bank', 'fk_account', 'rowid = ' . $parent_paiement->getData('fk_bank')));
            $reglement_mode = $this->db->getRow('c_paiement', 'id = ' . $pay->fk_paiement);
            
            if ($client->getData('is_subsidiary')) {
                $this->compte_general = $client->getData('accounting_account');
                $is_client_interco = true;
            }else {
                $this->compte_general  = '41100000';
            }
            
            $codeJournalByModeReglement = [
                'LIQ'       => $entrepot->code_journal_compta,
                'CHQ'       => $this->codeJournalCheque,
                'CB'        => $entrepot->code_journal_compta,
                'AE'        => $entrepot->code_journal_compta,
                'VIR'       => $compte_bancaire->cegid_journal,
                'PRE'       => $compte_bancaire->cegid_journal,
                'PRELEV'    => $compte_bancaire->cegid_journal,
                'FIN'       => 'OD',
                'SOFINC'    => 'OD',
                'CG'        => 'OD',
                'FIN_YC'    => 'OD'
            ];
            
            $compteByModeReglement = [
                'LIQ'       => $entrepot->compte_comptable,
                'CHQ'       => $this->compteCheque,
                'CB'        => $entrepot->compte_comptable_banque,
                'AE'        => $entrepot->compte_comptable_banque,
                'VIR'       => $compte_bancaire->compte_compta,
                'PRE'       => $compte_bancaire->compte_compta,
                'PRELEV'    => $compte_bancaire->compte_compta,
                'FIN'       => '41199000',
                'SOFINC'    => '41199100',
                'CG'        => '41199200',
                'FIN_YC'    => '51151200'
            ];
            
            $affichageByModeReglement = [
                'LIQ'       => 'ESP',
                'SOFINC'    => 'FIN',
                'CG'        => 'CHQ',
                'FIN_YC'    => 'YOU'
            ];

            $structure = Array();
            $structure['JOURNAL']           = sizing((array_key_exists($reglement_mode->code, $compteByModeReglement) ? $codeJournalByModeReglement[$reglement_mode->code] : $entrepot->code_journal_compta), 3);
            $structure['DATEP']             = sizing($datep->format('dmY'), 8);
            $structure['TYPE_PIECE']        = sizing('RC', 2);
            $structure['COMPTE']            = sizing((array_key_exists($reglement_mode->code, $compteByModeReglement) ? $compteByModeReglement[$reglement_mode->code] : $entrepot->compte_comptable ), 17);
            $structure['TYPE_COMPTE']       = sizing('X', 1);
            $structure['CODE_COMPTA']       = sizing('', 16);
            $structure['NEXT']              = sizing('', 1);
            $structure['REF']               = sizing($parent_paiement->getRef(), 35);
            $structure['LABEL']             = sizing(($reglement_mode->code == 'CG' ? '' : (($transaction->amount > 0) ? 'PAY' : 'REM')) . ' clt ' . $code_compta . ' ' . $datep->format('dmY'), 35);
            $structure['MODE_REGLEMENT']    = sizing((array_key_exists($reglement_mode->code, $affichageByModeReglement) ? $affichageByModeReglement[$reglement_mode->code] : $reglement_mode->code), 3, true);
            $structure['DATE_REGLEMENT']    = sizing($datep->format('dmY'), 8);
            $structure['SENS']              = sizing(($transaction->amount < 0) ? 'C' : 'D', 1);
            $structure['MONTANT']           = sizing(abs(round($transaction->amount, 2)), 20, true);
            $structure['TYPE_ECRITURE']     = sizing('N', 1);
            $structure['NUMERO_UNIQUE']     = sizing(substr(preg_replace('~\D~', '', $parent_paiement->getRef()), 1, 8), 8, true);
            $structure['DEVICE']            = sizing('EUR', 3);
            $structure['TAUX_DEV']          = sizing('1', 10, true);
            $structure['CODE_MONTANT']      = sizing('E--', 3);
            $structure['MONTANT_2']         = sizing('', 20);
            $structure['MONTANT_3']         = sizing('', 20);
            $structure['ETABLISSEMENT']     = sizing('001', 3);
            $structure['AXE']               = sizing('', 2);
            $structure['NUM_ECHEANCE']      = sizing('0', 2);
            $structure['FACTURE']           = sizing($facture->getRef(), 35);
            $structure['DATE_REF_EXTERNE']  = sizing('01011900', 8);
            $structure['DATE_CREATION']     = sizing($datec->format('dmY'), 8);
            $structure['SOCIETE']           = sizing('', 3);
            $structure['AFFAIRE']           = sizing('', 17);
            $structure['DATE_TAUX_DEV']     = sizing('01011900', 8);
            $structure['NOUVEAU_ECRAN']     = sizing('N', 3);
            $structure['QUANTITE_1']        = sizing('', 20);
            $structure['QUANTITE_2']        = sizing('', 20);
            $structure['QTY_QUALITATIVE_1'] = sizing('', 3);
            $structure['QTY_QUALITATIVE_2'] = sizing('', 3);
            $structure['REF_LIBRE']         = sizing(strtoupper('export automatique bimp erp'), 35);
            $structure['REGIME_TVA']        = sizing('-FRA', 4);
            $structure['TVA']               = sizing('', 3);
            $structure['TPF']               = sizing('', 3);
            $structure['CONTRE_PARTIE']     = sizing($this->compte_general, 17);
            $structure['VIDE']              = sizing($code_compta, 34);
            $structure['DATE_1']            = sizing('01011900', 8);
            $structure['DATE_2']            = sizing('01011900', 8);
            $structure['DATE_3']            = sizing('01011900', 8);
            $structure['VIDE_2']            = sizing('', 454);
            $structure['DATE_4']            = sizing('01011900', 8);
            $structure['VIDE_3']            = sizing('', 65);
            $structure['DATE_5']            = sizing('01011900', 8);
            $structure['DATE_6']            = sizing('01011900', 8);
            $structure['VIDE_4']            = sizing('', 5);
            $structure['LETTRAGE']          = sizing('-XRI', 4);
            $structure['VIDE_5']            = sizing('', 154);
            $structure['ETAT']              = sizing('-', 1);
            $structure['VIDE_6']            = sizing('', 59);
            $structure['FIN']               = sizing('0', 2);
            
            $this->rapportTier = $this->TRA_tiers->rapport;
            
            if(strpos($parent_paiement->getRef(), 'PAYNI') !== false) {
                $structure['JOURNAL']       = sizing('OD', 3);
                $structure['COMPTE']        = sizing($this->compte_general, 17);
                $structure['VIDE']          = sizing('CATTEN0000000000', 34);
                $structure['CODE_COMPTA']   = sizing('CATTEN0000000000', 16);
            }
            
            $ecriture .= implode('', $structure) . "\n";
            
            $structure['COMPTE']        = sizing($this->compte_general, 17);
            $structure['CODE_COMPTA']   = sizing($code_compta, 16);
            $structure['SENS']          = sizing(($transaction->amount > 0) ? 'C' : 'D', 1);
            $structure['CONTRE_PARTIE'] = sizing($this->compte_general, 17);
            $structure['VIDE']          = sizing('', 34);
            $structure['LETTRAGE']          = sizing('-XAL', 4);
            
            $ecriture .= implode('', $structure) . "\n";
            
            return $ecriture;
            
        }
        
        public function isVenteTicket($id):bool {
            
            $bc_paiement = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Paiement');

            if ($bc_paiement->find(['id_paiement' => $id])) {
                $this->caisse = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Caisse', $bc_paiement->getData('id_caisse'));
                if ($bc_paiement->getData('id_vente') > 0) {
                    $this->vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', $bc_paiement->getData('id_vente'));
                    if ($this->vente->getData('id_client') == 0) {
                        return 1;
                    }
                } else {
                }
            }

            return 0;
        }
        
    }
