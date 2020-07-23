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
        $all = json_decode($this->getData('serials_' . $sens));
        $html = "";
        foreach($all as $serial) {
            $html .= $serial . "<br />";
        }
        return $html;
    }
    
    public function getExtraBtn() {
        $buttons = [];
        
        $parent = $this->getParentInstance();
        
        if($parent->getData('statut') == 0) {
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
        
        $in = ($this->getData('serials_in')) ? json_decode($this->getData('serials_in')) : [];
        $out = ($this->getData('serials_out')) ? json_decode($this->getData('serials_out')) : [];
        
        $toOld = $data->old_serials;
        $toNew = explode("\n", $data->new_serials);
        
        $cloneOut = $out;
        
        foreach($out as $serial) { // Virer les serials décochez
            if(!in_array($serial, $toOld)) {
                $key = array_search($serial, $out);
                unset($out[$key]);
            }
        }
        foreach($toOld as $serial) { // Ajouter serial dans out et supprimer de in
            if(!in_array($serial, $out))
                $out[] = $serial;
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
        $in = json_decode($this->getData('serials_in'));
        $out = json_decode($this->getData('serials_out'));
        
        foreach($in as $serial) { $all[$serial] = $serial; }
        foreach($out as $serial) { $all[$serial] = $serial; }
        
        return $all;
    }
    
    public function checkSerial() {
        $list = $this->getallSerials();
        $out = json_decode($this->getData('serials_out'));
        foreach($list as $id => $element) {
            if(in_array($element, $out))
                $values[] = $id;
        }
        return $values;
    }
    
    public function getHtServ() {
        if($this->getData('id_line_contrat')) {
            $line = $this->getInstance('bimpcontract', 'BContract_contratLine', $this->getData('id_line_contrat'));
            $id_produit = $line->getData('fk_product');
            return $line->getData('total_ht') . "€";
        } else {
            $id_produit = $this->getData('id_serv');
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