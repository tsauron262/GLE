<?php

class Bimp_CommissionApporteur extends BimpObject{
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('success'))
    );
    
    public function create(&$warnings = array(), $force_create = false): string {
        $errors = parent::create($warnings, $force_create);
        if(!count($errors))
            $errors = BimpTools::merge_array ($errors, $this->addNewFatureLine ());
        return $errors;
    }
    
    public function addNewFatureLine(){
        $errors = array();
        
        
        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres');
        
        $factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
        
        foreach($tabsFiltres as $filtreObj){
            $idProd = $filtreObj->getProductIds();
            $filters = array(
                'f.fk_product' => array('IN' => $idProd),
                'commission_apporteur' => array('<' => '0'),
                'f.fk_facture' => array('IN' => "SELECT DISTINCT(`element_id`) FROM `llx_element_contact` WHERE `fk_c_type_contact` = (SELECT rowid FROM `llx_c_type_contact`  WHERE `code` = 'APPORTEUR' and `source` = 'external' AND `element` = 'facture') AND `fk_socpeople` IN (SELECT `rowid` FROM `llx_socpeople` WHERE `fk_soc` = ".$parent->getData('id_fourn').")")
            );
            
            
            $list = $factureLine->getList($filters, null, null, null, null, 'array', null, array('f' => array(
                                                'table' => 'facturedet',
                                                'alias' => 'f',
                                                'on'    => 'a.id_line = f.rowid')));
            
            foreach($list as $line){
                if(!$this->db->execute('UPDATE llx_bimp_facture_line SET commission_apporteur = "'.$this->id.'-'.$filtreObj->id.'" WHERE id_line = '.$line['id_line']))
                        $errors[] = 'Probléme de MAJ ligne';
            }
        }
        
        if(!count($errors))
            $errors = $this->calcTotal ();
        
        return $errors;
    }
    
    public function actionDelLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Ligne supprimé de la commission';
        
        if(isset($data['idLn']) && isset($data['idFiltre'])){
            $factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine', $data['idLn']);
            $apporteur = $this->getParentInstance();
            $filtreObj = $apporteur->getChildObject('filtres', $data['idFiltre']);
            if(is_object(($filtreObj)) && $filtreObj->isLoaded()){
                $factureLine->updateField('commission_apporteur', 0);
            }
            else{
                $errors[] = 'Impossible de charger le bon filtre '.$data['idFiltre'];
            }
        }
        else{
            $errors[] = 'Probléme de paramétres';
        }
        if(!count($errors))
            $errors = $this->calcTotal();
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    public function calcTotal(){
        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres');
        $tot = 0;
        foreach($tabsFiltres as $filtre){
            $res = $this->db->executeS("SELECT SUM(total_ht) as tot FROM `llx_facturedet` f, llx_bimp_facture_line bf WHERE bf.`id_line` = f.rowid AND `commission_apporteur` = '".$this->id."-".$filtre->id."'");
            $tot += $res[0]->tot * $filtre->getData('commition') / 100;
        }
        $this->updateField('total', $tot);
    }
    
    public function actionAddNewFatureLine($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Commissions ajoutées avec succès';

        $errors = $this->addNewFatureLine();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
        
    public function getActionsButtons()
    {

//        $buttons = parent::getActionsButtons();

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('addNewFatureLine')) {
                if ($this->canSetAction('addNewFatureLine')) {
                    $buttons[] = array(
                        'label'   => 'Ajouter commissions',
                        'icon'    => 'envelope',
                        'onclick' => $this->getJsActionOnclick('addNewFatureLine', array(), array(
                        ))
                    );
                }
            }
        }
        return $buttons;
    }
    
    
    public function renderDetailsView()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la commission absent');
        }

        $html = '';
        
        $parent = $this->getParentInstance();
        $tabsFiltres = $parent->getChildrenObjects('filtres');
        
        
        $factureLine = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
        $list_name = 'commission_apporteur';
        foreach($tabsFiltres as $filtre){
            $bc_list = new BC_ListTable($factureLine, $list_name, 1, null, 'Ligne de facture commisionnée à '.$filtre->getData('commition').' %', 'fas_check');
            $bc_list->addFieldFilterValue('commission_apporteur', $this->id.'-'.$filtre->id);
            $bc_list->addIdentifierSuffix('comm_'.$filtre->id.'_' . $this->id);

            $html .= $bc_list->renderHtml();
        }
        
        $html .= BimpRender::renderNavTabs($tabs, 'commission_details');

        return $html;
    }

}