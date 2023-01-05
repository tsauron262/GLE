<?php

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';

class TRA_importPaiement {
    
    protected $ref;
    protected $db;
            
    function __construct($bimp_db) {
        $this->db = $bimp_db;
    }
    
    public function constructTRA($line) {
        
        $raisonArray = (array) json_decode(BimpCore::getConf('comptes_importPaiement', null, "bimptocegid"));
        
        $importPaiement = $line->getParentInstance();
        $bank = $this->db->getRow('bank_account', 'rowid = ' . $importPaiement->getData('banque'));
        $date = new DateTime($line->getData('date'));
        
        $data['infos'] = '';
        
        if(preg_match('/(DV)[0-9]{14}/', $line->getData('data'), $matches) || preg_match('/(FV)[0-9]{14}/', $line->getData('data'), $matches) || preg_match('/(TK)[0-9]{14}/', $line->getData('data'), $matches) || preg_match('/(CV)[0-9]{14}/', $line->getData('data'), $matches)){//C2BO
            $data['infos'] = $matches[0];
        }
        
        $structure = Array();
        $structure['JOURNAL']           = sizing(($line->getData('num') ? 'OD' : $bank->cegid_journal), 3);
        $structure['DATE']              = sizing($date->format('dmY'),8);
        $structure['TYPE_PIECE']        = sizing("RC", 2);
        $structure['COMPTE']            = sizing(($line->getData('num') ? '41100000' : $bank->compte_compta), 17);
        $structure['TYPE_COMPTE']       = sizing('X', 1);
        $structure['CODE_COMPTA']       = sizing(($line->getData('num') ? 'CATTEN0000000000' : ''), 16);
        $structure['NEXT']              = sizing('',1);
        $structure['REF']               = sizing(($line->getData('num') ? $line->getData('num') : 'IP' . $importPaiement->id . '-' . $line->id), 35);
        $structure['LABEL']             = sizing(suppr_accents($line->getData('name')), 35);
        $structure['MODE_REGLEMENT']    = sizing('VIR', 3, true);
        $structure['DATE_REGLEMENT']    = sizing($date->format('dmY'), 8);
        $structure['SENS']              = sizing('D', 1);
        $structure['MONTANT']           = sizing(abs(round($line->getdata('price'), 2)), 20, true);
        $structure['TYPE_ECRITURE']     = sizing('N',1);
        $structure['NUMERO_PIECE']      = sizing($line->id, 8, true);
        $structure['DEVISE']            = sizing("EUR", 3);
        $structure['TAUX_DEV']          = sizing("1", 10,true);
        $structure['CODE_MONTANT']      = sizing("E--", 3);
        $structure['MONTANT_2']         = sizing("", 20);
        $structure['MONTANT_3']         = sizing("", 20);
        $structure['ETABLISSEMENT']     = sizing("001", 3);
        $structure['AXE']               = sizing("", 2);
        $structure['NUM_ECHEANCE']      = sizing("0", 2);
        $structure['REF_EXTERNE']       = sizing(($line->getData('num') ? $line->getData('num') : $data['infos']), 35);
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
        $structure['REFERENCE_LIBRE']   = sizing(($line->getData('num') ? $line->getData('num') : $data['infos']), 35);
        $structure['REGIME_TVA']        = sizing("-FRA", 4);
        $structure['TVA']               = sizing("", 3);
        $structure['TPF']               = sizing("", 3);
        $structure['CONTRE_PARTIE']     = sizing("", 17);
        $structure['VIDE']              = sizing(($line->getData('num') ? 'CATTEN0000000000' : $bank->compte_compta), 34);
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
        
        $ecriture = implode('', $structure) . "\n";
        
        $structure['COMPTE']            = sizing($raisonArray[$line->getData('infos')], 17);
        $structure['CODE_COMPTA']       = sizing('', 16);
        $structure['SENS']              = sizing('C', 1);
        
        $ecriture .= implode('', $structure) . "\n";
        
        return $ecriture;
        
    }
    
}