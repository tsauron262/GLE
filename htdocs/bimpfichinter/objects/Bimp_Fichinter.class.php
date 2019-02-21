<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/objects/BimpDolObject.class.php';

class Bimp_Fichinter extends BimpDolObject
{
    public $force_update_date_ln = true;
    public static $dol_module = 'fichinter';
    public $extraFetch = false;

    public static $nature_list = array(
        0 => array('label' => 'Choix', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Installation', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Dépannage', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Télémaintenance', 'icon' => 'check', 'classes' => array('info')),
        4 => array('label' => 'Formation', 'icon' => 'check', 'classes' => array('info')),
        5 => array('label' => 'Audit', 'icon' => 'check', 'classes' => array('info')),
        6 => array('label' => 'Suivi', 'icon' => 'check', 'classes' => array('info')),
    );
    
    
    public static $type_list = array(
        -1 => array('label' => 'Choix', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Forfait', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Sous garantie', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Contrat', 'icon' => 'check', 'classes' => array('info')),
        4 => array('label' => 'Temps pass&eacute;', 'icon' => 'check', 'classes' => array('warning')),
    );
    
    
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info'))
    );


    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            return $this->getData('ref');
        }

        return ' ';
    }
    
    public function getCommercialSearchFilters(&$filters, $value, &$joins = array(), $main_alias = 'a'){
        $joins["commerciale"] = array("table" => "societe_commerciaux", "alias"=>"sc", "on"=> "sc.fk_soc = " . $main_alias . ".fk_soc");
        $filters["sc.fk_user"]  = $value;
    }
    
    public function displayCommercial()
    {
        global $user;
        $html = "";
        if ($this->isLoaded() && $this->getData("fk_soc") > 0) {
            $soc = $this->getInstance("bimpcore", "Bimp_Societe");
            $soc->fetch($this->getData("fk_soc"));
            $userT = $this->getInstance("bimpcore", "Bimp_User");
            foreach($soc->dol_object->getSalesRepresentatives($user) as $userTab){
                $userT->fetch($userTab['id']);
                $html .= $userT->dol_object->getNomUrl(1);
            }
            
        }

        return $html;
    }

    
    public function traiteDate(){
        if($this->getData("datei") != $this->getInitData("datei") && $this->force_update_date_ln){
            $lines = $this->getChildrenObjects("lines");
            foreach($lines as $line){
                $line->set ("datei", $this->getData("datei"));
                $line->update();
            }
            
        }
    }
    
    
    
    
    
    public function getData($field) {
        if(stripos($field, "extra") !== 0)
            return parent::getData($field);
        else{
            return $this->getExtra($field);
        }
    }
    
    public function set($field, $value) {
        if(stripos($field, "extra") !== 0)
            parent::set($field, $value);
        else{
            return $this->setExtra($field, $value);
        }
    }
    
    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false) {
        if(stripos($field, "extra") !== 0)
            parent::updateField($field, $value, $id_object, $force_update, $do_not_validate);
    }
    
    public function getExtra($field){
        $field = str_replace("extra", "", $field);
        if ($this->isLoaded()){
            if(!$this->extraFetch){
                $this->dol_object->fetch_extra();
                $this->extraFetch = true;
            }
            return $this->dol_object->extraArr[$field];
        }
    }
    
    public function setExtra($field, $value){
        $field = str_replace("extra", "", $field);
        $this->dol_object->setExtra($field, $value);
    }
       
    public function update(&$warnings = array(), $force_update = false) {
        $this->traiteDate();
        
        foreach($this->data as $nom => $val){
            if(stripos($nom, "extra") === 0){
                $this->setExtra($nom, $val);
                unset($this->data[$nom]);
            }
        }
        
        parent::update($warnings, $force_update);
    }

   
    
    
    
    
    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $langs->load('propal');

        $buttons = array();

        if ($this->isLoaded()) {
            $buttons[] = array(
                'label'   => 'Générer le PDF',
                'icon'    => 'fas_sync',
                'onclick' => $this->getJsActionOnclick('generatePdf', array(), array())
            );
        }
        return $buttons;
    }
    
//public function updateDolObject(&$errors) {
//    parent::updateDolObject($errors);
//}

}

