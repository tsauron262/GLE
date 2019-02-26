<?php

require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/extraFI.class.php';

class Bimp_Demandinter extends extraFI
{
    public $moduleRightsName = "synopsisdemandeinterv";
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
    
    public static $type2_list = array(
        -1 => array('label' => 'Choix', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Forfait', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Sous garantie', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Contrat', 'icon' => 'check', 'classes' => array('info')),
        4 => array('label' => 'Temps pass&eacute;', 'icon' => 'check', 'classes' => array('warning')),
    );
    
    
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'En cours', 'icon' => 'check', 'classes' => array('warning')),
        3 => array('label' => 'Cloturé', 'icon' => 'check', 'classes' => array('success'))
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
    
    
       
    public function update(&$warnings = array(), $force_update = false) {
        $this->traiteDate();
        
        
        parent::update($warnings, $force_update);
        
        $this->dol_object->synchroAction();
    }

   
    
    
    
    
    public function getActionsButtons()
    {
        global $conf, $langs, $user;
        $langs->load('propal');

        $buttons = array();

        if ($this->isLoaded()) {
            if($this->getData("fk_user_prisencharge") != $user->id)
                $buttons[] = array(
                    'label'   => 'Prendre en charge',
                    'icon'    => 'fas_user',
                    'onclick' => $this->getJsActionOnclick('priseEnCharge', array($user->id), array())
                );
            if($this->getData("fk_user_prisencharge")> 0)
                $buttons[] = array(
                    'label'   => 'Créer une FI',
                    'icon'    => 'fas_ambulance',
                    'onclick' => $this->getJsActionOnclick('createFi', array($user->id), array())
                );
        }
        return $buttons;
    }
    
    public function actionCreateFi(){
        $this->dol_object->createFi(false);
    }
    
    public function actionPriseEnCharge($params){
        $this->updateField("fk_user_prisencharge", $params[0]);
    }
    
//public function updateDolObject(&$errors) {
//    parent::updateDolObject($errors);
//}
    
    
    
    
    
    
    
    
    
    

}

