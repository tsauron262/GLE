<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_avenant.class.php';

class BContract_avenantdet extends BContract_avenant {
    
    function __construct($module, $object_name) {
        return parent::__construct($module, $object_name);
    }
    
    public function getServiceDesc() {
        
        if($this->getData('id_line_contrat')) {
            $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
            $id_produit = $line->getData('fk_product');
        } else {
            $id_produit = $this->getData('id_serv');
        }
        
        $p = $this->getInstance('bimpcore', 'Bimp_Product', $id_produit);
        
        return $p->getData('ref');
        
    }
    
    public function displaySerial($sens) {
        $all = BimpTools::json_decode_array($this->getData('serials_' . $sens));
        $html = "";
        $fl = true;
        foreach($all as $serial) {
            if($fl) {
                $fl = false;
                $html .= $serial;
            } else {
                $html .= ', ' . $serial;
            }
            
        }
        return $html;
    }
    
    public function getCoup($display = true) {
        $html = '<strong>';
        $priceForOne = $this->getCurrentPriceForQty();
        //if($priceForOne > 0) {
            //Calcule des ajouts
            $qtyUp = $this->getQtyAdded();
            $coupUp = $qtyUp * $priceForOne;

            // Calcule des supprétions
            $qtyDown = $this->getQtyDeleted();
            $coupDown = $qtyDown * $priceForOne;

            $coup = $coupUp - $coupDown;
            $class = "warning";
            $icon = "arrow-right";

            if($coup > 0) {
                $class = "success";
                $icon = "arrow-up";
            } elseif($coup < 0) {
                $class = "danger";
                $icon = "arrow-down";
            }

            $html .= '<strong class="'.$class.'" >' . BimpRender::renderIcon($icon) . ' '.price($coup).'€</strong>';
//        }  else {
//            $html .= '<strong class="info">Facturation indépandante</strong>';
//        }
        
        
        
        $html .= '</strong>';
        
        if($display)
            return $html;
        else
            return $coup;
    }
    
    public function displayAllMouvementDeThune() {
        
        $html = "<strong>";
        
        $html .= "Quantité courante: " . $this->getCurrentQtyDet() . "<br />";
        if($this->getData('id_line_contrat')) {
            $html .= "Quantité ajoutée: <strong class='success' >".$this->getQtyAdded()."</strong><br />";
            $html .= "Quantité supprimée: <strong class='danger' >".$this->getQtyDeleted()."</strong><br />";
        }
        $html .= "Prix courant pour 1 au prorata: <strong class='success'>".price($this->getCurrentPriceForQty())."€</strong><br />";
        
        $html .= '</strong>';
        return $html;
    }
    
    public function getCurrentPriceForQty($prorata = true) {
        $contrat = null;
        if($this->getData('id_line_contrat')) {
            $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
            $price = $line->getData('price_ht');
            
            if($prorata) {
                $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $_REQUEST['id']);            
            }
            
        } else {
            $p = $this->getInstance('bimpcore', 'Bimp_Product', $this->getData('id_serv'));
            if($prorata) {
                $contrat = $this->getInstance('bimpcontract', 'BContract_contrat', $_REQUEST['id']);
            }
            $price = $p->getData('price');
        }
        
        if(is_object($contrat)) {
            if($contrat->isLoaded()) {
                $total_days_contrat = $contrat->getEndDate()->diff(new DateTime($contrat->getData('date_start')))->days;
                $parent = $this->getParentInstance();
                $date_effect = new DateTime($parent->getData('date_effect'));
                $reste_days_from_effect = $contrat->getEndDate()->diff($date_effect)->days;
                $price_per_one_day = ($price / $total_days_contrat);
                
                $price = ($price_per_one_day * $reste_days_from_effect);
                
            } else {
                return -1;
            }
        }
        
        return $price;
    }
    
    public function getCurrentTotalDet() {
        $qty = $this->getCurrentQtyDet();
        
    }
    
    public function getCurrentQtyDet() {
        return count(json_decode($this->getData('serials_in')));
    }
    
    public function getTotalAdded() {
        
    }
    
    public function getTotalDeleted() {
        
    }
    
    public function getQtyAdded() {
        if($this->getData('id_line_contrat')) {
            $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
            $init_serials_array = json_decode($line->getData('serials'));
            $current_serials_array = json_decode($this->getData('serials_in'));
            return  count(array_diff($current_serials_array, $init_serials_array));
        } else {
            return count(json_decode($this->getData('serials_in')));
        }
    }
    
    public function getQtyDeleted() {
        return count(json_decode($this->getData('serials_out')));
    }
    
    public function getExtraBtn() {
        $buttons = [];
        
        $parent = $this->getParentInstance();
        
        if($parent->getData('statut') == 0) {
            $buttons[] = array(
                'label'   => 'Modifier le libellé',
                'icon'    => 'fas_tag',
                'onclick' => $this->getJsActionOnclick('modifLabel', array(), array(
                    'form_name' => 'modifLabel'
                ))
            );
            if($this->getData('in_contrat')) {
                $buttons[] = array(
                    'label'   => 'Gérer le numéros de série',
                    'icon'    => 'fas_cogs',
                    'onclick' => $this->getJsActionOnclick('manageSerials', array(), array(
                        'form_name' => 'manageSerials'
                    ))
                );
            }
            
            if($this->getData('in_contrat')) {
                $buttons[] = array(
                    'label'   => 'Supprimer la ligne de l\'avenant',
                    'icon'    => 'fas_trash',
                    'onclick' => $this->getJsActionOnclick('delLine', array(), array(

                    ))
                );
            } else {
                $buttons[] = array(
                    'label'   => 'Remettre la ligne sur l\'avenant',
                    'icon'    => 'fas_arrow-left',
                    'onclick' => $this->getJsActionOnclick('reLine', array(), array(

                    ))
                );
            }
            
        }
        
        
        
        return $buttons;
    }
    
    public function actionManageSerials($data, &$success) {
        $errors = [];
        $warnings = [];
        $success = '';        
        $data = (object) $data;
                
        $in = ($this->getData('serials_in')) ? BimpTools::json_decode_array($this->getData('serials_in')) : [];
        $out = ($this->getData('serials_out')) ? BimpTools::json_decode_array($this->getData('serials_out')) : [];
        
        $toOld = $data->old_serials;
        if(!is_array($toOld))
            $toOld = array($toOld);
        $toNew = explode("\n", $data->new_serials);
        
        $cloneOut = $out;
        
        foreach($out as $serial) { // Virer les serials décochez
            if(!in_array($serial, $toOld)) {
                $key = array_search($serial, $out);
                unset($out[$key]);
            }
        }
        foreach($toOld as $serial) { // Ajouter serial dans out et supprimer de in
            if(!in_array($serial, $out)) {
                $contratLine = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
                $serials_in_contratLine = (json_decode($contratLine->getData('serials')) ? json_decode($contratLine->getData('serials')) : []);
                if(in_array($serial, $serials_in_contratLine)) // Si le serial enlever est pas dans les serials de la ligne de base du contrat
                    $out[] = $serial;
            }
            // Virer les serial cochés dans le tableau de in
            if(in_array($serial, $in)) {
                $key = array_search($serial, $in);
                unset($in[$key]);
            }
        }
        
        foreach($cloneOut as $serial) { // Ajout des serials décocher
            if(!in_array($serial, $out) && !in_array($serial, $in)) {
                $in[] = $serial;
            }
        }
        
        foreach($toNew as $serial) {
            if(!in_array($serial, $in)) {
                $in[] = $serial;
            }
        }
        
        $toUpdateIn = [];
        $toUpdateOut = [];
        
        foreach($in as $serial) {
            if($serial)
                $toUpdateIn[] = $serial;
        }
        
        foreach($out as $serial) {
            if($serial)
                $toUpdateOut[] = $serial;
        }
        
        $errors = $this->updateField('serials_in', json_encode($toUpdateIn));
        if(!count($errors)) {
            $errors = $this->updateField('serials_out', $toUpdateOut);
            if(!count($errors)) {
                $success = "Changement des numéros de série effectués avec succès";
            }
        }
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
    
    public function getLineSerials($sens = null) {
        
        $all = json_decode($this->getData('serials_' . $sens));

        $toSend = [];
        foreach($all as $serial) {
            if($serial)
                $toSend[$serial] = $serial;
        }
        
        return $toSend;
        
    }
    
    public function getallSerials() {
        $all = [];
        $in = BimpTools::json_decode_array($this->getData('serials_in'));
        $out = BimpTools::json_decode_array($this->getData('serials_out'));
        
        foreach($in as $serial) { $all[$serial] = $serial; }
        foreach($out as $serial) { $all[$serial] = $serial; }
        
        return $all;
    }
    
    public function checkSerial() {
        $list = $this->getallSerials();
        $out = BimpTools::json_decode_array($this->getData('serials_out'));
        foreach($list as $id => $element) {
            if(in_array($element, $out))
                $values[] = $id;
        }
        return $values;
    }
    
    public function getHtServ() {
        if($this->getData('id_line_contrat')) {
            $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
            return $line->getData('total_ht') . "€";
        } else {
            return $this->getData('ht') . "€";
        }
        
        //$p = $this->getInstance('bimpcore', 'Bimp_Product', $id_produit);
        
        
    }
    
    public function actionDelLine() {
        $errors = [];
        $warnings = [];
        $success = "";
        
        $errors = $this->updateField('in_contrat', 0);
        
        if(!count($errors)) {
            $success = "La ligne ne sera pas pris en compte dans l'avenant"; 
        }
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
    
    public function actionModifLabel($data, &$success) {
        $errors = [];
        $warnings = [];
        $success = "";
        
        $errors = $this->updateField('description', $data['label']);
        if(!count($errors))
            $success = "Label modifié avec succès";
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
    
    public function actionReLine() {
        $errors = [];
        $warnings = [];
        $success = "";
        
        $errors = $this->updateField('in_contrat', 1);
        
        if(!count($errors)) {
            $success = "La ligne à été réactivée dans l'avenant"; 
        }
        
        return [
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }

}