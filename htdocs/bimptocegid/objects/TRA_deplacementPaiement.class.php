<?php
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/code_journal.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/objects/TRA_tiers.class.php';
    
    class TRA_deplacementPaiement {
        
        protected $db;
        protected $compteGeneralFrom;
        protected $compteGeneralTo;
        public $rapport = [];
        public $caisse;
        private $TRA_tiers;
        public $rapportTier;
        
        
        function __construct($bimp_db, $tiers_file) { 
            $this->db = $bimp_db; 
            $this->TRA_tiers = new TRA_tiers($bimp_db, $tiers_file);
        }
        
        public function constructTra($paiement, $datas) {
            
            $factureFrom        = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $datas->id_facture_from);
            $factureTo          = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $datas->id_facture_to);
            $clientFrom         = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $factureFrom->getData('fk_soc'));
            $clientTo           = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $factureTo->getData('fk_soc'));
            $datec              = new DateTime($paiement->getData('datec'));
            $datep              = new DateTime($paiement->getData('datep'));
            $entrepotFrom       = $this->db->getRow('entrepot', 'rowid = ' . ($this->isVenteTicket($paiement->id) ? $this->caisse->getData('id_entrepot') : $factureFrom->getData('entrepot')));
            $entrepotTo         = $this->db->getRow('entrepot', 'rowid = ' . ($this->isVenteTicket($paiement->id) ? $this->caisse->getData('id_entrepot') : $factureTo->getData('entrepot')));
            $codeComptaFrom     = ($this->isVenteTicket($paiement->id)) ? $entrepotFrom->compte_aux : $this->TRA_tiers->getCodeComptable($clientFrom);
            $codeComptaTo       = ($this->isVenteTicket($paiement->id)) ? $entrepotTo->compte_aux : $this->TRA_tiers->getCodeComptable($clientTo);
            $compte_bancaire    = $this->db->getRow('bank_account', 'rowid = ' . $this->db->getValue('bank', 'fk_account', 'rowid = ' . $paiement->getData('fk_bank')));
            $reglement          = $this->db->getRow('c_paiement', 'id = ' . $paiement->getData('type'));
            
            if ($clientFrom->getData('is_subsidiary')) {
                $this->compteGeneralFrom = $clientFrom->getData('accounting_account');
            }else {
                $this->compteGeneralFrom  = '41100000';
            }
            
            if ($clientTo->getData('is_subsidiary')) {
                $this->compteGeneralTo = $clientTo->getData('accounting_account');
            }else {
                $this->compteGeneralTo  = '41100000';
            }
            
            $codeJournalByModeReglementFrom = [
                'LIQ'       => $entrepotFrom->code_journal_compta,
                'CHQ'       => $entrepotFrom->code_journal_compta,
                'CB'        => $entrepotFrom->code_journal_compta,
                'AE'        => $entrepotFrom->code_journal_compta,
                'VIR'       => $compte_bancaire->cegid_journal,
                'PRE'       => $compte_bancaire->cegid_journal,
                'PRELEV'    => $compte_bancaire->cegid_journal,
                'FIN'       => 'OD',
                'SOFINC'    => 'OD',
                'CG'        => 'OD',
                'FIN_YC'    => 'OD'
            ];
            
            $compteByModeReglementFrom = [
                'LIQ'       => $entrepotFrom->compte_comptable,
                'CHQ'       => $entrepotFrom->compte_comptable_banque,
                'CB'        => $entrepotFrom->compte_comptable_banque,
                'AE'        => $entrepotFrom->compte_comptable_banque,
                'VIR'       => $compte_bancaire->compte_compta,
                'PRE'       => $compte_bancaire->compte_compta,
                'PRELEV'    => $compte_bancaire->compte_compta,
                'FIN'       => '41199000',
                'SOFINC'    => '41199100',
                'CG'        => '41199200',
                'FIN_YC'    => '51151200'
            ];

            $codeJournalByModeReglementTo = [
                'LIQ'       => $entrepotTo->code_journal_compta,
                'CHQ'       => $entrepotTo->code_journal_compta,
                'CB'        => $entrepotTo->code_journal_compta,
                'AE'        => $entrepotTo->code_journal_compta,
                'VIR'       => $compte_bancaire->cegid_journal,
                'PRE'       => $compte_bancaire->cegid_journal,
                'PRELEV'    => $compte_bancaire->cegid_journal,
                'FIN'       => 'OD',
                'SOFINC'    => 'OD',
                'CG'        => 'OD',
                'FIN_YC'    => 'OD'
            ];
            
            $compteByModeReglementTo = [
                'LIQ'       => $entrepotTo->compte_comptable,
                'CHQ'       => $entrepotTo->compte_comptable_banque,
                'CB'        => $entrepotTo->compte_comptable_banque,
                'AE'        => $entrepotTo->compte_comptable_banque,
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
            
            $rmb = ($paiement->getData('amount') < 0) ? true : false;
            
            // Toujours FactureFrom
            $structure = Array();
            $structure['JOURNAL']           = sizing('OD', 3);
            $structure['DATEP']             = sizing($datep->format('dmY'), 8);
            $structure['TYPE_PIECE']        = sizing('RC', 2);
            $structure['COMPTE']            = sizing('41100000', 17);
            $structure['TYPE_COMPTE']       = sizing('X', 1);
            $structure['CODE_COMPTA']       = sizing($codeComptaFrom, 16);
            $structure['NEXT']              = sizing('', 1);
            $structure['REF']               = sizing($paiement->getRef(), 35);
            $structure['LABEL']             = sizing($factureFrom->getRef() . 'to' . $factureTo->getRef(), 35);
            $structure['MODE_REGLEMENT']    = sizing((array_key_exists($reglement->code, $affichageByModeReglement) ? $affichageByModeReglement[$reglement->code] : $reglement->code), 3, true);
            $structure['DATE_REGLEMENT']    = sizing($datep->format('dmY'), 8);
            $structure['SENS']              = sizing(($rmb) ? 'C' : 'D', 1);
            $structure['MONTANT']           = sizing(abs(round($datas->montant, 2)), 20, true);
            $structure['TYPE_ECRITURE']     = sizing('N', 1);
            $structure['NUMERO_UNIQUE']     = sizing('D' . substr(preg_replace('~\D~', '', $paiement->getRef()), 1, 7), 8, true);
            $structure['DEVICE']            = sizing('EUR', 3);
            $structure['TAUX_DEV']          = sizing('1', 10, true);
            $structure['CODE_MONTANT']      = sizing('E--', 3);
            $structure['MONTANT_2']         = sizing('', 20);
            $structure['MONTANT_3']         = sizing('', 20);
            $structure['ETABLISSEMENT']     = sizing('001', 3);
            $structure['AXE']               = sizing('', 2);
            $structure['NUM_ECHEANCE']      = sizing('0', 2);
            $structure['FACTURE']           = sizing($factureTo->getRef(), 35);
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
            $structure['CONTRE_PARTIE']     = sizing($this->compteGeneralTo, 17);
            $structure['VIDE']              = sizing($codeComptaFrom, 34);
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
            $ecriture .= implode('', $structure) . "\n";
            
            // Toujours FactureTo
            $structure['CODE_COMPTA']       = sizing($codeComptaTo, 16);
            $structure['SENS']              = sizing(($rmb) ? 'D' : 'C', 1);
            $structure['CONTRE_PARTIE']     = sizing($this->compteGeneralFrom, 17);
            $structure['VIDE']              = sizing($codeComptaTo, 34);
            
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