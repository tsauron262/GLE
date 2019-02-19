<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/objects/BimpDolObject.class.php';

class Bimp_Fichinter extends BimpDolObject
{
    public $force_update_date_ln = true;
    public static $dol_module = 'fichinter';

    public static $nature_list = array(
        0 => array('label' => 'Choix', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Installation', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Dépannage', 'icon' => 'check', 'classes' => array('info')),
        3 => array('label' => 'Télémaintenance', 'icon' => 'check', 'classes' => array('info')),
        4 => array('label' => 'Formation', 'icon' => 'check', 'classes' => array('info')),
        5 => array('label' => 'Audit', 'icon' => 'check', 'classes' => array('info')),
        6 => array('label' => 'Suivi', 'icon' => 'check', 'classes' => array('info')),
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
    
    public function traiteDate(){
        if($this->getData("datei") != $this->getInitData("datei") && $this->force_update_date_ln){
            $lines = $this->getChildrenObjects("lines");
            foreach($lines as $line){
                $line->set ("datei", $this->getData("datei"));
                $line->update();
            }
            
        }
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
    

    
    public function update(&$warnings = array(), $force_update = false) {
        $this->traiteDate();
        
        parent::update($warnings, $force_update);
    }

  
}

