<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfi/objects/BimpFi_fiche.class.php';

class BimpFi_inter extends BimpFi_fiche {
    
    CONST TYPE_ANCIENNE = 0;
    CONST TYPE_DEPLACEMENT = 1;
    CONST TYPE_IMPONDERABLE = 2;
    CONST TYPE_INTERVENTION = 3;
    CONST STATUT_BROUILLON = 0;
    
    public static $type_pointage = [
        self::TYPE_ANCIENNE => ['label' => "Ancienne version", 'icon' => 'refresh', 'classes' => ['info']],
        self::TYPE_DEPLACEMENT => ['label' => "Deplacement", 'icon' => 'car', 'classes' => ['warning']],
        self::TYPE_IMPONDERABLE => ['label' => "Impondérable", 'icon' => 'times', 'classes' => ['danger']],
        self::TYPE_INTERVENTION => ['label' => "Intervention", 'icon' => 'cogs', 'classes' => ['success']]
    ];
    
    public function displayTechsArray($nom_url = true, $br = false) {
        $tech = $this->getInstance('bimpcore', 'Bimp_User');
        $html = "";
        $first = true;
        foreach(json_decode($this->getData('techs')) as $id) {
            if($id > 0) {
                $tech->fetch((int) $id);
                if($first) {
                    $first = false;
                } else {
                    if($br)
                        $html .= '<br />';
                    else
                        $html .= ', ';
                }
                $html .= $tech->dol_object->getNomUrl();
                
            }
        }
        return $html;
    }
    
    public function displayDetails() {
        $html = "";
        $html .= "<h4>".$this->getData('description')."</h4><hr>";
        $html .= "Intervenants: " . $this->displayTechsArray(true, true);
        return $html;
    }
    
    public function getListExtraButtons()
    {
        $buttons = [];
        $buttons[] = array(
            'label'   => 'Ajouter une action',
            'icon'    => 'fas_plus',
            'onclick' => $this->getJsActionOnclick('addPointage', array(), array(
                'form_name' => 'addPointage'
            ))
        );
        return $buttons;
    }
    
}