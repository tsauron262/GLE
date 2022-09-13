<?php

    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/code_journal.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    
    class TRA_bordereauCHK {
        
        protected $db;
        public $rapport = [];
        protected $compteCheque = '51124000';
        protected $compteBanque = '51240900';
        protected $codeJournal  = 'CA';
        
        function __construct($bimp_db) { 
            $this->db = $bimp_db; 
        }
        
        public function constructTra($bordereau, $cheques) {
            
            $date = new DateTime($bordereau->date_bordereau);
            $ecriture  = "";
            $structure = Array();

            $structure['JOURNAL']           = sizing($this->codeJournal, 3);
            $structure['DATE']              = sizing($date->format('dmY'),8);
            $structure['TYPE_PIECE']        = sizing("RC", 2);
            $structure['COMPTE']            = sizing($this->compteBanque, 17);
            $structure['TYPE_COMPTE']       = sizing('X', 1);
            $structure['CODE_COMPTA']       = sizing('', 16);
            $structure['NEXT']              = sizing('',1);
            $structure['REF']               = sizing($bordereau->ref, 35);
            $structure['LABEL']             = sizing(suppr_accents(strtoupper('remise de ' . $bordereau->nbcheque . ' ' . (($bordereau->nbcheque > 1) ? 'cheques' : 'cheque'))), 35);
            $structure['MODE_REGLEMENT']    = sizing('CHQ', 3, true);
            $structure['DATE_REGLEMENT']    = sizing($date->format('dmY'), 8);
            $structure['SENS']              = sizing('D', 1);
            $structure['MONTANT']           = sizing(abs(round($bordereau->amount, 2)), 20, true);
            $structure['TYPE_ECRITURE']     = sizing('N',1);
            $structure['NUMERO_PIECE']      = sizing($bordereau->rowid, 8, true);
            $structure['DEVISE']            = sizing("EUR", 3);
            $structure['TAUX_DEV']          = sizing("1", 10,true);
            $structure['CODE_MONTANT']      = sizing("E--", 3);
            $structure['MONTANT_2']         = sizing("", 20);
            $structure['MONTANT_3']         = sizing("", 20);
            $structure['ETABLISSEMENT']     = sizing("001", 3);
            $structure['AXE']               = sizing("", 2);
            $structure['NUM_ECHEANCE']      = sizing("0", 2);
            $structure['FACTURE']           = sizing("", 35);
            $structure['DATE_REF_EXTERNE']  = sizing("01011900", 8);
            $structure['DATE_CREATION']     = sizing($date->format('dmY'), 8);
            $structure['SOCIETE']           = sizing("", 3);
            $structure['AFFAIRE']           = sizing("", 17);
            $structure['DATE_TAUX_DEV']     = sizing("01011900", 8);
            $structure['NOUVEAU_ECRAN']     = sizing("N", 3);
            $structure['QUANTITE_1']        = sizing("", 20);
            $structure['QUANTITE_2']        = sizing("", 20);
            $structure['QUANTITE_QUALI_1']  = sizing("", 3);
            $structure['QUANTITE_QUALI_2']  = sizing("", 3);
            $structure['REFERENCE_LIBRE']   = sizing("", 35);
            $structure['REGIME_TVA']        = sizing("-FRA", 4);
            $structure['TVA']               = sizing("", 3);
            $structure['TPF']               = sizing("", 3);
            $structure['CONTRE_PARTIE']     = sizing($this->compteCheque, 17);
            $structure['VIDE']              = sizing("", 34);
            $structure['DATE_1']            = sizing("01011900", 8);
            $structure['DATE_2']            = sizing("01011900", 8);
            $structure['DATE_3']            = sizing("01011900", 8);
            $structure['VIDE_2']            = sizing("", 454);
            $structure['DATE_4']            = sizing("01011900", 8);
            $structure['VIDE_3']            = sizing("", 65);
            $structure['DATE_5']            = sizing("01011900", 8);
            $structure['DATE_6']            = sizing("01011900", 8);
            $structure['VIDE_4']            = sizing("", 5);
            $structure['LETTRAGE']          = sizing("-XRI", 4);
            $structure['VIDE_5']            = sizing("", 154);
            $structure['ETAT']              = sizing("-", 1);
            $structure['VIDE_6']            = sizing("", 59);
            $structure['FIN']               = sizing("0", 2);
            
            $ecriture .= implode('', $structure);
            $ecriture .= "\n";
            foreach($cheques as $cheque) {
                $structure['COMPTE']            = sizing($this->compteCheque, 17);
                $structure['CONTRE_PARTIE']     = sizing($this->compteBanque, 17);
                $structure['SENS']              = sizing('C', 1);
                
                $paiement = $this->db->getRow('paiement', 'fk_bank = ' . $cheque->rowid);
                
                $listPaiements = $this->db->getRows('paiement_facture', 'fk_paiement = ' . $paiement->rowid);
                
                foreach($listPaiements as $paiementFacture) {
                    $structure['REF']               = sizing($paiement->ref, 35);
                    $structure['LABEL']             = sizing(suppr_accents(strtoupper('remise cheque ' . $cheque->num_chq)), 35);
                    $facture = $this->db->getRow('facture', 'rowid = ' . $paiementFacture->fk_facture);
                    $structure['FACTURE']           = sizing($facture->facnumber, 35);
                    $structure['MONTANT']           = sizing(abs(round($paiementFacture->amount, 2)), 20, true);
                    $ecriture .= implode('', $structure) . "\n";
                }
                
            }
            
            return $ecriture;
            
        }
    }