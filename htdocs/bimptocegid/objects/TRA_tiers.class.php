<?php
    
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sizing.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/suppr_accent.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/interco_code.php';
    require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/functions/sens.php';
    
    class TRA_tiers {
        
        protected $db;
        public $rapport = [];
        private $exclude_charaters = ["'", '"', "-", "(", ")", ".", ";", ':', "/", "!", "_", "+", "=","é", "à", "è", "&", "ç", "?", "ù", "%", "*", ",", '#', '@'];
        public $tier;
        private $file;
        private $tiers_type_client = [8 => 'P', 5 => 'A'];
        private $type_tier;
        public $justView = false;
        
        function __construct($bimp_db, $file) { 
            $this->db = $bimp_db; 
            $this->file = $file;
        }

        public function cleanString($string) {
            
            $string = suppr_accents($string);
            
            foreach($this->exclude_charaters as $bad_char) {
                $string = str_replace($bad_char, '', $string);
            }
            
            return $string;
            
        }
        
        public function getCodeComptable($tier, $field = 'code_compta', $createTiers = true) {
            if($createTiers) {
                if($tier->getData('exported') && $tier->getData($field) && !BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                    return $tier->getData($field);
                } else {
                    $this->tier = $tier;
                    return $this->constructTra($field);
                }
            } else {
                return ($tier->getData($field)) ? $tier->getData($field) : 'xxxxxxxxxxxxxxxx';
            }
            
        }
        
        public function define_code_aux($field) {
            
            $name = strtoupper($this->cleanString($this->tier->getName()));
            
            switch($field) {
                case 'code_compta':
                    if(array_key_exists($this->tier->getData('fk_typent'), $this->tiers_type_client)) {
                        $first = $this->tiers_type_client[$this->tier->getData('fk_typent')];
                    } else {
                        $first = 'E';
                    }
                    break;
                default:
                    $first = 'F';
                    break;
            }
            
            $this->type_tier = $first;
            
            $zip = ($this->tier->getData('zip')) ? substr($this->tier->getData('zip'), 0, 2)  : '00';
            
            switch($first) {
                case 'P':
                    $arrayOfName = explode(' ', $name);
                    if(isset($arrayOfName[1])){
                        $auxName  = substr($arrayOfName[0],0,7);
                        $auxName .= substr($arrayOfName[1],0,3);
                    }
                    else
                        $auxName = substr(str_replace(' ', '', $name),0,10);
                    break;
                default:
                    $auxName = substr(str_replace(' ', '', $name),0,10);
                    break;
            }
            
            if(strlen($auxName) < 10)
                $auxName = sizing($auxName, 10, false, true, false);
            
            $classement = $this->db->getCount('societe', 'code_compta LIKE "'. $first . $zip . $auxName .'%"', 'rowid');
            
            return $first . $zip . $auxName . sizing($classement, 3, false, false, true);
            
        }
        
        protected function getCompte400($field) {
            
            switch($field) {
                case 'code_compta':
                    return ($this->tier->getData('is_subsidiary')) ? $this->tier->getData('accounting_account') : '41100000';
                    break;
                case 'code_compta_fournisseur':
                    return ($this->tier->getData('is_subsidiary')) ? $this->tier->getData('accounting_account_fournisseur') : '40100000';
                    break;
            }
            
        }
        
        protected function getCommercialId() {
            return $this->db->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $this->tier->id);
        }
        
        protected function getCountry() {
            return $this->db->getRow('c_country', '`rowid` = ' . $this->tier->getData('fk_pays'));
        }
                
        public function constructTra($field) {
            
            $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $this->getCommercialId());
            $country = $this->getCountry();
            
            $majCodeAux = false;
            $codeAux = $this->tier->getData($field);
            if(is_null($codeAux) || $codeAux == ''){
                $majCodeAux = true;
                $codeAux = $this->define_code_aux($field);
            }
            
            $structure = Array();
            $structure['FIXE']            = sizing('***', 3);
            $structure['JOURNAL']         = sizing('CAE', 3);
            $structure['AUXILIAIRE']      = sizing($codeAux, 17);
            $structure['LABEL']           = sizing(strtoupper($this->cleanString($this->tier->getName())), 35);
            $structure['NATURE']          = sizing(($field == 'code_compta') ? 'CLI' : 'FOU', 3);
            $structure['LETTRAGE']        = sizing('X', 1);
            $structure['COMPTE_GENERAL']  = sizing($this->getCompte400($field), 17);
            $structure['EAN']             = sizing(($field == 'code_compta') ? strtoupper(($commercial->isLoaded()) ? $commercial->getData('login') : 'PRDIRECTION') : '', 17);
            $structure['TABLE_1']         = sizing(($field == 'code_compta') ? 'AUTO' : '', 17);
            $structure['TABLE_2']         = sizing('', 17);
            $structure['TABLE_3']         = sizing(($this->tier->getData('is_salarie')) ? 'SA' : '', 17);
            $structure['TABLE_4']         = sizing(($field == 'code_compta') ? ($this->type_tier == 'P') ? 'PAR' : 'PRO' : '', 17);
            $structure['TABLE_5']         = sizing('', 17);
            $structure['TABLE_6']         = sizing('', 17);
            $structure['TABLE_7']         = sizing('', 17);
            $structure['TABLE_8']         = sizing('', 17);
            $structure['TABLE_9']         = sizing('', 17);
            $structure['TABLE_10']        = sizing('', 17);
            if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                $structure['ADRESSE']         = sizing(strtoupper($this->cleanString($this->tier->getData('address'))), 35);
                $structure['VIDE']            = sizing('', 70);
                $structure['CODE_POSTAL']     = sizing($this->tier->getData('zip'), 9);
                $structure['VILLE']           = sizing(strtoupper($this->cleanString($this->tier->getData('town'))), 35);
                $structure['VIDE_2']          = sizing('', 47);
            }
            else{
                $structure['ADRESSE']         = sizing('', 35);
                $structure['VIDE']            = sizing('', 70);
                $structure['CODE_POSTAL']     = sizing('', 9);
                $structure['VILLE']           = sizing('', 35);
                $structure['VIDE_2']          = sizing('', 47);
            }
            $structure['PAYS']            = sizing($country->code_iso, 3);
            $structure['NOM_ABREGE']      = sizing(strtoupper($this->cleanString(str_replace(' ', '', $this->tier->getName()))), 17);
            $structure['LANGUE']          = sizing(strtoupper($country->code), 3);
            $structure['MULTI_DEVICE']    = sizing(($field == 'code_compta') ? '-' : '', 1);
            $structure['DEVICE_TIER']     = sizing('EUR', 3);
            if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                $structure['TELEPHONE']       = sizing($this->cleanString($this->tier->getData('phone')), 25);
                $structure['FAX']             = sizing('', 25);
            }
            else{
                $structure['TELEPHONE']       = sizing('', 25);
                $structure['FAX']             = sizing('', 25);
            }
            $structure['REGIME_TVA']      = sizing(($country->in_ue) ? $country->code_iso : '', 3);
            $id_reglement        = ($this->tier->getData('mode_reglement') > 0) ? $this->tier->getData('mode_reglement') : 6;
            $reglement           = $this->db->getRow('c_paiement', 'id = ' . $id_reglement);
//            $structure['MODE_REGLEMENT']  = sizing('', 3);
            $structure['MODE_REGLEMENT']  = sizing(($reglement->code == 'LIQ') ? 'ESP' : $reglement->code, 3);
            $structure['VIDE_3']          = sizing('', 52);
            $structure['SIRET']           = sizing(($country->code == 'FR') ? $this->tier->getData('siret') : $this->tier->getData('idprof4'), 17);
            $structure['APE']             = sizing($this->tier->getData('ape'), 5);
            $structure['PRENOM']          = sizing('', 35);
            $structure['VIDE_4']          = sizing('', 70);
            $structure['VIDE_5']          = sizing('', 75);
            if(Bimpcore::getConf('mode_detail', null, 'bimptocegid')){
                $structure['ADRESSE_EMAIL']   = sizing(suppr_accents($this->tier->getData('email')), 54);
            }
            else{
                $structure['ADRESSE_EMAIL']   = sizing('', 54);
            }
            $structure['STATUT_JURIDIQUE']= sizing(suppr_accents($this->tier->displayJuridicalStatus()), 3);
            $structure['RIB']             = sizing('-', 1);
            $structure['TVA_ENCAISSEMENT']= sizing('TM', 3);
            $structure['PAYEUR']          = sizing('', 17);
            $structure['IS_PAYEUR']       = sizing('-', 1);
            $structure['AVOIR']           = sizing('-', 1);
            $structure['VIDE_6']          = sizing('', 6);
            $structure['CONF']            = sizing('0', 1);
            $structure['VIDE_7']          = sizing('', 156);
            
            if(!$this->justView) {
                if($this->file != '')
                    $this->write(implode('', $structure), $structure['AUXILIAIRE']);
            
                $this->rapport[$structure['AUXILIAIRE']] = 'Créé dans le fichier ' . $this->file;
                if($majCodeAux)
                    $this->tier->updateField($field, $codeAux);
                return $structure['AUXILIAIRE'];
            } else {
                return implode('', $structure);
            }
            
        }
        
        protected function write($ecriture) {
            
            $file = fopen($this->file, 'a+');
            fwrite($file, $ecriture . "\n");
            fclose($file);
            $this->tier->updateField('exported', 1);
        }

    }