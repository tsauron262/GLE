<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimptechnique/objects/BT_ficheInter.class.php';

class BT_ficheInter_facturation extends BT_ficheInter {
    
    public function displayTTC() {
        return price(($this->getData('tva_tx')  * $this->getData('total_ht_vendu')) / 100) . " €";
    }
    
    public function displayHTfacture() {
        
        $remise = $this->getdata('remise');
        $ht_vendu = $this->getData('total_ht_vendu');
        $ht_depacement = $this->getData('total_ht_depacement');
        
        
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
        $facturable = ($this->getData('facturable')) ? true : false;

        if(/*$parent->getData('fk_statut') == 1 */ $this->getData('total_ht_depacement') > 0) {
            $buttons[] = array(
                'label' => "Appliquer une remise",
                'icon' => 'fas_percent',
                'onclick' => $this->getJsActionOnclick('addRemise', array(), array(
                    'form_name' => "addRemise"
                ))
            );
        }

        return $buttons;
    }
    
    public function actionAddRemise($data, &$success) {
        
    }

}