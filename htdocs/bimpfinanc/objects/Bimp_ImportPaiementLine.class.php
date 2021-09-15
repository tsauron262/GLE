<?php

class Bimp_ImportPaiementLine extends BimpObject{
    var $types = array('' => '', 'vir' => 'Virement');
    var $refs = array();
    var $total_reste_a_paye = 0;
    var $ok = false;
    
    
    function create(&$warnings = array(), $force_create = false) {
        $errors = parent::create($warnings, $force_create);
        
        $result = $this->actionInit();
        $errors = BimpTools::merge_array($errors, $result['errors']);
        
        return $errors;
    }
    
    function actionInit(){
        $errors = $warnings = array();
        
        $price = 0;
        if (preg_match('/MMOEUR2[0]*([0-9\.,]+)/', $this->getData('data'), $matches)) {
            $price = $matches[1]/100;   
        }

        $type = '';
        if(stripos($this->getData('data'), '0517806000000669EUR2E0416135704405') !== false){
            $type = 'vir';
        }
        
        
        $name = '';
//        if (preg_match('/ [0-9]{6} *(.+) *[0-9]{22}/', $this->getData('data'), $matches)) {
//            $name = $matches[1];   
//        }
        if (preg_match('/0517806000000669EUR2E0416135704405[0-9]{6}(.+)/', $this->getData('data'), $matches)) {
            $name = str_replace('NPY', '', trim($matches[1]));
        }
        
        $this->set('factures', array());
        $this->set('price', $price);
        $this->set('type', $type);
        $this->set('name', $name);
        $errors = $this->update($warnings);
        
        $this->calc();
        
        $facts = $this->getFactReconnue();
        $totReste = $price;
        $ids = array();
        foreach($facts as $fact){
            $totReste -= $fact->getData('remain_to_pay');
            $ids[] = $fact->id;
        }
        if($totReste == 0){
            $this->addFact($ids);
        }
        
        
        return array('errors' => $errors, 'warnings' => $warnings);
    }
    
    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && $this->isEditable()) {

            $buttons[] = array(
                'label'   => 'Reinitialiser la ligne',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('init')
            );
        }
        return $buttons;
    }
    
    function calc(){
        $ln = $this->getData('data');
            
        $matches = array();
        $price = 0;

        if (preg_match_all('/(FA[A-Z]*[0-9]{4})[\-_ ]?([0-9]{5})/', $ln, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $matche){
                $this->refs[$matche[1].'-'.$matche[2]] = $matche[1].'-'.$matche[2];
            }

        }
        
        foreach($this->getData('factures') as $idF){
            $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idF); 
                   
            $this->total_reste_a_paye += $obj->getData('remain_to_pay');
        }
        
        
        if($this->getData('price') - $this->total_reste_a_paye == 0)
            $this->ok = true;
    }
    
    function fetch($id, $parent = null) {
        parent::fetch($id, $parent);
        if($this->isLoaded())
            $this->calc();
    }
    
    function actionAddFact($data){
        $this->addFact($data['id']);
        return array('errors' => $errors, 'warnings' => $warnings);
    }
    
    function addFact($ids){
        $errors = $warnings = array();
        if(!is_array($ids))
            $ids = array($ids);
        $tab1 = BimpTools::merge_array($this->getData('factures'), $ids);
        $tab2 = array();
        foreach($tab1 as $id){
            $tab2[$id] = $id;
        }
        $this->updateField('factures', $tab2);
    }
    
    function getRowStyle(){
        if($this->ok)
        return 'background-color:green!important;opacity: 0.2;';
    }
    
    
    function getFactPossible(){
        global $db;
        $return = array();
        if(!$this->ok && $this->getData('price') > 0){
            $sql = $db->query('SELECT SUM(remain_to_pay) as remain_to_pay_tot, fk_soc FROM `llx_facture` WHERE paye = 0 GROUP BY fk_soc HAVING remain_to_pay_tot = '.$this->getData('price'));
            while($ln = $db->fetch_object($sql)){
                $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $ln->fk_soc);
                $facts = array();
                $list = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('paye'=>0, 'fk_soc'=>$ln->fk_soc));
                foreach($list as $fact){
                    $facts[] = $fact->getLink() . $this->getButtonAdd($fact->id);
                }
                $return[] = $cli->getLink().' ('.implode(' - ', $facts).')';
            }
        }
        return implode('<br/>', $return); 
    }
    
    function getFactClient(){
        $return = array();
        if(!$this->ok && $this->getData('price') > 0 && $this->getData('name') != ''){
            $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe');
            $results = $cli->getSearchResults('default', $this->getData('name'));
            if($results){
                foreach($results as $result){
                    $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $result['id']);
              
                    $facts = array();
                    $list = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('paye'=>0, 'fk_soc'=>$cli->id));
                    foreach($list as $fact){
                        $facts[] = $fact->getLink() . $this->getButtonAdd($fact->id); 
                    }
                    $return[] = $cli->getLink().' ('.implode(' - ', $facts).')';
                }
            }
            else{
                $return[] = $name;
            }
        }
        return implode('<br/>', $return); 
    }
    
    function getDataInfo(){
        $return = '<span class=" bs-popover"';
        $return .= BimpRender::renderPopoverData($this->getData('data'), 'top', true);
        $return .= '>';
        $return .= substr($this->getData('data'), 0, 30).'...';
        $return .= '</span>';
        return $return;
    }
    
    function getButtonAdd($id){
        if($this->isEditable()){
            $html .= '<span type="button" class="btn btn-default btn-small" onclick="';
            $html .= $this->getJsActionOnclick('addFact', array('id'=>$id)).'">';
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft').' Ajouter';
            $html .= '</span>';
            return $html;
        }
    }
    
    function getFactReconnue(){
        $return = array();
        $refs = $this->refs;
        foreach($refs as $ref){
            $obj = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array('facnumber' => $ref));
            if($obj && $obj->isLoaded()){
                $return[] = $obj;
            }
        }
        return $return;
    }
    
    function displayFactReconnue(){
        $refs =$this->refs;
        foreach($refs as $ref){
            $obj = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array('facnumber' => $ref));
            if($obj && $obj->isLoaded()){
                $html = $obj->getLink();
                $html .= $this->getButtonAdd($obj->id);
                
                
                $refs[$ref] = $html;
            }
        }
        
        return implode(' - ', $refs );
    }
    
    function isEditable($force_edit = false, &$errors = array()): int {
        return !$this->getData('traite');
    }
    
    function getPrice(){
        if($this->getData('type') == 'vir'){
            if($this->getData('traite') == 0){
                $manque = ($this->getData('price') - $this->total_reste_a_paye);
                return BimpRender::renderAlerts($this->getData('price') . ' € - ' . $this->total_reste_a_paye . ' € = ' .$manque .' €', ($manque == 0? 'success' : 'danger'));
            }
            else
                return BimpRender::renderAlerts($this->getData('price') . ' €', ($manque == 0? 'success' : 'danger'));
        }
        return 'Non géré';
    }
}
