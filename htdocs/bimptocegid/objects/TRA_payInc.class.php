<?php

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';

class TRA_payInc {
    
    protected $ref;
    protected $db;
            
    function __construct($bimp_db) { $this->db = $bimp_db;}
    
    public function getAllForThisDay():array {
        BimpObject::loadClass('bimpfinanc', 'Bimp_ImportPaiement');
        return Bimp_ImportPaiement::toCompteAttente();
    }
    
    public function setTraite($id):bool {
        if($this->db->update('Bimp_ImportPaiementLine', Array('id_user_traite' => 460, 'traite' => 1), "id = $id")) {
            return 1;
        }
        return 0;
    }
    
    public function constructTRA($data = Array()):string  {
        
        $date = new DateTime($data['date']);
        $ecriture  = "";
        $structure = Array();
        
        $banqueData = $this->db->getRow('bank_account', 'rowid = '.$data['banque']);
        
        $structure['JOURNAL']           = sizing($banqueData->cegid_journal, 3);
        $structure['DATE']              = sizing($date->format('dmY'),8);
        $structure['TYPE_PIECE']        = sizing("RC", 2);
        $structure['COMPTE']            = sizing($banqueData->compte_compta, 17);
        $structure['TYPE_COMPTE']       = sizing('X', 1);
        $structure['CODE_COMPTA']       = sizing('', 16);
        $structure['NEXT']              = sizing('',1);
        $structure['REF']               = sizing($data['num'], 35);
        $structure['LABEL']             = sizing(suppr_accents($data['name']), 35);
        $structure['MODE_REGLEMENT']    = sizing('VIR', 3, true);
        $structure['DATE_REGLEMENT']    = sizing($date->format('dmY'), 8);
        $structure['SENS']              = sizing('D', 1);
        $structure['MONTANT']           = sizing(abs(round($data['amount'], 2)), 20, true);
        $structure['TYPE_ECRITURE']     = sizing('N',1);
        $structure['NUMERO_PIECE']      = sizing($data['id'], 8, true);
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
        $structure['REFERENCE_LIBRE']   = sizing("EXPORT " . $data['num'] . " BIMP-ERP", 35);
        $structure['REGIME_TVA']        = sizing("-FRA", 4);
        $structure['TVA']               = sizing("", 3);
        $structure['TPF']               = sizing("", 3);
        $structure['CONTRE_PARTIE']     = sizing("47100000", 17);
        $structure['VIDE']              = sizing("CATTEN0000000000", 34);
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
        
        $ecriture .= implode("", $structure) . "\n";
        
        $structure['COMPTE']            = sizing('41100000', 17);
        $structure['CODE_COMPTA']       = sizing('CATTEN0000000000', 16);
        $structure['CONTRE_PARTIE']     = sizing("51240900", 17);
        $structure['VIDE']              = sizing("", 34);
        $structure['LETTRAGE']          = sizing("-XRI", 4);
        $structure['SENS']              = sizing('C', 1);
        
        $ecriture .= implode("", $structure) . "\n";
        
        return $ecriture;
        
    }
    
    
    
    
}