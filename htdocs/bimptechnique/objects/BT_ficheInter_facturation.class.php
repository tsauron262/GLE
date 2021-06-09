<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

class BT_ficheInter_facturation extends BT_ficheInter {
    
    public $juste_depacement_facturable = true;
    
    public function displayTTC() {
        return price( $this->getData('total_ht_vendu') + (($this->getData('tva_tx') * $this->getData('total_ht_vendu')) / 100) ) . " €";
    }
    
    public function displayHTfacture() {
        
        $return = "";
        
        $remise = $this->getdata('remise');
        $ht_vendu = $this->getData('total_ht_vendu');
        $ht_depacement = $this->getData('total_ht_depacement');
        
        if($this->juste_depacement_facturable) {
            $return .= "<strong class='info' >" . BimpRender::renderIcon("warning") . " Juste le dépacement est facturable</strong><br />";
            $facturable = ($ht_depacement - ($remise * $ht_depacement) / 100);
        } else {
            $facturable = (($ht_depacement + $ht_vendu) - ($remise * $ht_depacement) / 100);
        }
        
        $return .= $facturable . "€ HT";
        
        return $return;
        
    }
    
    public function displayAlertes() {
        
        $html = "";
        
        if($this->getData('remise') > 5) {
            $html .= "<strong class='bs-popover rowButton' ".BimpRender::renderPopoverData('<strong>Soumis à falidation financière</strong><br />Pourcentage de plus de 5%', "top", true)." >".BimpRender::renderIcon("fas_percent")."</strong>";
        } 
        
        return $html;
        
    }
    
    public function displayFactureAssoc() {
        
    }
    
    public function displayAssocLines() {
        $parent = $this->getParentInstance();
        $html = "";
        $lines = json_decode($this->getData('fi_lines'));
        
        if(count($lines) > 0) {
            $line = BimpCache::getBimpObjectInstance("bimptechnique", "BT_ficheInter_det");
            foreach($lines as $id_line) {
                $line->fetch($id_line);
                $html .= "#" . $line->id . " " . $line->display_service_ref(false)  . "<br />";
            }
        }
        return $html;
    }
    
    public function displayCommande()  {
        
        if($this->getData('id_commande') > 0) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $this->getData('id_commande'));
            return $commande->getNomUrl(true, false, true, "default", "default");
        } else {
            return '<strong class="danger">Pas de commande associée</strong>';
        }
        
    }
    
    public function getListFilterDefault(){
        return Array(
          Array(
              'name' => 'fk_fichinter',
              'filter' => $_REQUEST['id']
          )
        );
    }
    
    public function getListExtraButtons() {
        global $conf, $langs, $user;
        $buttons = Array();
        $parent = $this->getParentInstance();

        if($parent->getData('fk_statut') == 1 && $this->getData('total_ht_depacement') > 0) {
            $buttons[] = array(
                'label' => "Appliquer une remise",
                'icon' => 'fas_percent',
                'onclick' => $this->getJsActionOnclick('addRemise', array(), array(
                    'form_name' => "addRemisePercent"
                ))
            );
        }

        return $buttons;
    }
    
    public function canEditField($field_name) {
        switch($field_name) {
            case "remise":
                return 1;
                break;
        }
        return 0;
    }
    
    public function actionAddRemise($data, &$success) {
        
        $warnings = Array();
        $errors = Array();
        
        if($data['remise'] != $this->getData('remise')) {
            $this->set('remise', $data['remise']);
            $this->update($warnings, true);
        }
        
        return Array(
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        );
        
    }
    
}