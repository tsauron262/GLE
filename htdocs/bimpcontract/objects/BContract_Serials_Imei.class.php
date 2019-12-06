<?php

class BContract_Serials_Imei extends BimpObject {
    
    CONST CONTRAT_IS_CLOS = 0;
    CONST CONTRAT_IS_OPEN = 1;
    
    public static $type_separator = [
        1 => 'Retour à la ligne',
        2 => 'Virgule',
        3 => 'Point virgule',
        4 => 'Fichier CSV'
    ];
    
    public function canClientView() {
        return 1;
    }
    
    public function canClientCreate() {
        return 0;
    }
    
    public function canClientDelete() {
        return 0;
    }
    
    public function canClientEdit() {
        return 0;
    }
    
    public function contrat_is_open() {
        
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getdata('id_contrat'));
        
        switch($contrat->getData('statut')) {
            case self::CONTRAT_IS_OPEN:
                $class = 'success';
                $icon = 'check';
                $label = 'Sous couverture';
                break;
            case self::CONTRAT_IS_CLOS:
                $class = 'danger';
                $icon = 'times';
                $label = 'Hors couverture';
                break;
        }
        
        return '<span class="'.$class.'" ><i class="fas fa-'.$icon.'" ></i> '.$label.'</span>';
    }
    
    public function getAssociateService() {
        $lines = explode(',', $this->getData('id_line'));
        if(!count($lines) || $lines[0] == 0) {
            return '';
        }
        $return = '';
        foreach($lines as $line => $id) {
            $the_line = $this->getInstance('bimpcontract', 'BContract_contratLine', intval($id));
            $produit = $this->getInstance('bimpcore', 'Bimp_Product', $the_line->getData('fk_product'));
            $return .= $produit->getNomUrl() . " ";
        }
        
        
        
        return $return;
    }
    
    public function getFilterList() {
        return Array(
            Array(
                'name' => 'id_contrat',
                'filter' => $_REQUEST['id']
            )
        );
    }
    
    public function getDefaultListExtraButtons()
    {
        
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $this->getData('id_contrat'));
        if($contrat->getData('statut') == self::CONTRAT_IS_OPEN){
            $buttons = array();
        
        
            $url = 'client.php?fc=contrat_ticket&id='.$contrat->id.'&navtab-maintabs=tickets';

            $callback = "window.open('" . DOL_URL_ROOT . "/bimpinterfaceclient/?fc=contrat_ticket&id=".$contrat->id."&navtab-maintabs=tickets', '_self');";

            if ($this->isLoaded()) {
                $buttons[] = array(
                    'label'   => 'Créer un ticket sur le bon contrat',
                    'icon'    => 'fas_plus-square',
                    'onclick' => $callback
                );
            }
            return $buttons;
        }
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $_REQUEST['id']);
        
        switch(BimpTools::getValue('attached_serials_separator')) {
            case 1:
                $tab_serials = explode("<br>", BimpTools::getValue('attached_serials'));
                break;
        }
        
        foreach ($tab_serials as $index => $serial) {
            if($serial != "") {
                $instance = $this->getInstance('bimpcontract', 'BContract_Serials_Imei');
                if($instance->find(['serial' => $serial, 'id_contrat' => $contrat->id])) {
                    $services = explode(',', $instance->getData('id_line'));
                    if(!in_array(BimpTools::getValue('attached_service'), $services)) {
                        $new_field_id_line = $instance->getData('id_line') . ',' . BimpTools::getValue('attached_service');
                        $this->db->update('bcontract_serials', ['id_line' => $new_field_id_line], 'id = ' . $instance->id);
                    }
                } else {
                  parent::create();
                    $this->set('serial', $serial);
                    $this->set('id_contrat', $_REQUEST['id']);
                    $this->set('id_line', BimpTools::getValue('attached_service'));
                    $this->update();
                }
            }
        }
        
    }

    public function list_service() {
        $liste = [];
        $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $_REQUEST['id']);
        foreach ($contrat->dol_object->lines as $line) {
            $product = $this->getInstance('bimpcore', 'Bimp_Product', $line->fk_product);
            $liste[$line->id] = $product->getData('ref') . ' - ' . $line->description;
        }
        return $liste;
    }

}