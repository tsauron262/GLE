<?php

class Bimp_SocBankAccount extends BimpObject
{

    public function displayRib()
    {
        $html = '';

        $err = array();
        $this->isValid($err);
        $class = 'objectSubList';
        
                
        $htmlSup = '';     
        if(count($err)){     
            $class = 'danger';
            $htmlSup .= '<span class="objectIcon bs-popover"';
            $htmlSup .= BimpRender::renderPopoverData(implode(' - ', $err));
            $htmlSup .= '>';
            $htmlSup .= BimpRender::renderIcon('fas_exclamation-triangle', 'danger');
            $htmlSup .= '</span>';
        }
        $html .= $htmlSup;

        $html .= '<table class="'.$class.'">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Prefix</th>';
        $html .= '<th>Banque</th>';
        $html .= '<th>Guichet</th>';
        $html .= '<th>N°</th>';
        $html .= '<th>Clé</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>' . $this->getData('iban_prefix') . '</td>';
        $html .= '<td>' . $this->getData('code_banque') . '</td>';
        $html .= '<td>' . $this->getData('code_guichet') . '</td>';
        $html .= '<td>' . $this->getData('number') . '</td>';
        $html .= '<td>' . $this->getData('cle_rib') . '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
                    
        $html .= $htmlSup;   
        
        return $html;
    }
    
    // Rights
    
    public function isEditable($force_edit = false, &$errors = array()) {
        return !$this->getData('exported');
    }
    
    public function isDeletable($force_delete = false, &$errors = array()) {
        return !$this->getData('exported');
    }
    
    public function getCodePays(){
        return substr(str_replace(" ", "", $this->getData('iban_prefix')),0,2);
    }
    
    public function getDevise(){
        $codeP = $this->getCodePays();
        $zone_euro = array('FR', 'BE');
        if(in_array($codeP, $zone_euro))
            return 'EUR';
        else
            return '';
    }
    
    // return boolean:
    
    public function getIban($withEspace = true){
        if($withEspace)
            $sep = ' ';
        else
            $sep = '';
        $return = substr(str_replace(" ", "", $this->getData('iban_prefix')),0,40).$sep;
        $return .= str_replace(" ", "", $this->getData('code_banque')).$sep;
        $return .= str_replace(" ", "", $this->getData('code_guichet')).$sep;
        $return .= str_replace(" ", "", $this->getData('number')).$sep;
        $return .= str_replace(" ", "", $this->getData('cle_rib')).$sep;
        
        return $return;
    }
    
    public function isValid(Array &$errors = Array()):bool {

        $rib = Array(
            "banque"        => (int) $this->getData('code_banque'),
            "agence"        => (int) $this->getData('code_guichet'),
            "compte"        => (string) $this->getData('number'),
            "compte_strtr"  => (int) strtr(strtoupper($this->getData("number")), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", '12345678912345678923456789'),
            "clerib"        => (int) $this->getData('cle_rib')
        );
        
        if($this->getDevise() == '')
            $errors[] = 'Devise inconnue';
        
        $cbX89 = 89 * $rib['banque'];
        $cgX15 = 15 * $rib['agence'];
        $ncX3 = 3 * $rib['compte_strtr'];
        
        $verif_key = (int) 97 - (($cbX89 + $cgX15 + $ncX3) % 97);
        
        if((int) $verif_key !== $rib['clerib']) {
            $errors[] = "Le RIB sélectionné n'est pas valide, veuillez vérifier qu'il ne comporte pas d'erreurs";
        }
        
        if($this->getData('rum') == ''){
            $errors[] = "Le RIB n'est pas valide (RUM absent)";
        }
        $iban = $this->getIban(false);
        if(strlen($iban) < 27 || strlen($iban) > 34){
            $errors[] = "Longeur IBAN invalide";
        }
            
        if(!count($errors))
            return (bool) 1;
        return 0;
    }

    // overrides: 
    
    
    public function getNumSepa()
    {
        if ($this->getData('rum') == "") {
            $new = BimpTools::getNextRef('societe_rib', 'rum', 'FR02ZZZ008801-', 7);
            return $new;
        }
        return $this->getData('rum');
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $this->set('rum', $this->getNumSepa());
        $errors = parent::create($warnings, $force_create);
        

        if (!count($errors)) {
            // Le create du dol_object n'insert pas les valeurs...
            $errors = $this->update($warnings, $force_create);
        }
        return $errors;
    }
    
    public function getFileName($signe = false, $ext = '.pdf'){
        return $this->getData('fk_soc').'_'.$this->id.'_sepa'.($signe? '_signe' : '').$ext;
    }
    
    public function isFieldEditable($field, $force_edit = false) {
        global $user;
        if($field == 'rum' && $user->id != 7 && !$user->admin)
            return 0;
        if($field == 'exported')
            return 0;
        
        return parent::isFieldEditable($field, $force_edit);
    }
    
    public function update(&$warnings = array(), $force_update = false)
    {
        $this->set('rum', $this->getNumSepa());
        $def = (int) $this->getData('default_rib');
        
        if(isset($_FILES['file']) && $_FILES['file']['name'] != ''){
            $soc = $this->getChildObject('societe');
            $file_dir = $soc->getFilesDir();
            
            $oldName =  $_FILES['file']['name'];
            $name = $this->getFileName(true, '.'.pathinfo($oldName, PATHINFO_EXTENSION));
            $_FILES['file']['name']= $name;
            if(file_exists($file_dir.$_FILES['file']['name']))
                    $errors[] = 'Fichier '.$_FILES['file']['name']. ' existe déja';
            
            
            
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            
            dol_add_file_process($file_dir, 0, 0, 'file');
        }

        $errors = BimpTools::merge_array($errors, parent::update($warnings, $force_update));

        if (!count($errors)) {
            if ($def) {
                $this->db->update($this->getTable(), array(
                    'default_rib' => 0
                        ), 'fk_soc = ' . (int) $this->getData('fk_soc') . ' AND type = \'' . $this->getData('type') . '\' AND rowid != ' . (int) $this->id);
            }
        }

        return $errors;
    }
}
