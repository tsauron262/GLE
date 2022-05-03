<?php

class Bimp_ImportPaiementLine extends BimpObject
{

    var $types = array('' => 'N/C', 'vir' => 'Virement');
    var $refs = array();
    var $total_reste_a_paye = 0;
    var $ok = false;
    var $raisonManu = array(
        1 => 'C2BO',
        2 => 'Fournisseur',
        3 => 'ONEY',
        4 => 'YOUNITED',
        99 => 'Autre'
    );
    
    function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        $result = $this->actionInit();
        $errors = BimpTools::merge_array($errors, $result['errors']);

        return $errors;
    }

    function actionInit()
    {
        $errors = $warnings = array();

        $price = 0;
        if (preg_match('/MMOEUR2[0]*([0-9\.,]+)/', $this->getData('data'), $matches)) {
            $price = $matches[1] / 100;
        }

        $type = '';
        
        $codes = array(array('0517806000000669EUR2E0416135704405'), array('0417806000000669EUR2E04161357044C2', array('price' => 'methode2'))/*virement trés peut d'info*/);
        
        foreach ($codes as $data){
            $code = $data[0];
            
            if (stripos($this->getData('data'), $code) !== false) {
                $type = 'vir';
                if(isset($data[1]['price'])){
                    if($data[1]['price'] == 'methode2'){
                        $price = (substr(trim($this->getData('data')), -10, 10));
                        $lettre = substr($price, -1,1);
                        $price = intval(str_replace($lettre, $this->lettreToChiffre($lettre), $price)) / 100;
                    }
                }





                if (preg_match('/'.str_replace("", "", $code).'([0-9]{2})([0-9]{2})([0-9]{2})(.+)/', $this->getData('data'), $matches)) {
                    $date = '20' . $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                    $datebrut = $matches[1].$matches[2].$matches[3];
                }

                $name = '';
                if (preg_match('/'.$code.'[0-9]{6}(.+)/', $this->getData('data'), $matches)) {
                    if(stripos($matches[1], '0000000100000') !== false){
                        $tmp = explode('0000000100000', $matches[1]);
                        $matches[1] = $tmp[0];
                    }


                    $name = trim(str_replace(array('NPY', 'VIR'), '', str_replace($datebrut, '', trim($matches[1]))));
                }
            }
        }
        
        if(preg_match('/(DV)[0-9]{14}/', $this->getData('data'), $matches) || preg_match('/(FV)[0-9]{14}/', $this->getData('data'), $matches) || preg_match('/(TK)[0-9]{14}/', $this->getData('data'), $matches) || preg_match('/(CV)[0-9]{14}/', $this->getData('data'), $matches)){//C2BO
            $this->set('traite', 1);
            $this->set('infos', 'C2BO');
        }

        $this->set('factures', array());
        $this->set('price', $price);
        $this->set('type', $type);
        $this->set('name', $name);
        $this->set('date', $date);
        
        $errors = $this->update($warnings);

        $this->calc();

        $facts = $this->getFactReconnue();
        $totReste = $price;
        $ids = array();
        foreach ($facts as $fact) {
            $totReste -= $fact->getData('remain_to_pay');
            $ids[] = $fact->id;
        }
        if ($totReste == 0) {
            $this->addFact($ids);
        }

        return array('errors' => $errors, 'warnings' => $warnings);
    }
    
    private function lettreToChiffre($find){
        $array = array('}', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        foreach($array as $chiffre => $lettre)
            if($lettre == $find)
                return $chiffre;
        return 0;
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
            $buttons[] = array(
                'label'   => 'Lettrage manuel',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('traiteManuel', array(), array('form_name' => 'lettrage_man'))
            );
            $buttons[] = array(
                'label'   => 'Créer acompte',
                'icon'    => 'fas_euro-sign',
                'onclick' => $this->getJsActionOnclick('createAcompte', array(), array('form_name' => 'acompte'))
            );
        }
        return $buttons;
    }
    
    function getGlobalBtn(){
        $buttons = array();
        $parent = $this->getParentInstance();
        if ($parent->isActionAllowed('validate')) {
            if ($parent->canSetAction('validate')) {
                $buttons[] = array(
                    'label'   => 'Traiter les paiements rattachés',
                    'icon'    => 'fas_check',
                    'onclick' => $parent->getJsActionOnclick('create_all_paiement')
                );
            }
        }

        return $buttons;
    }

    function getButtonAdd($id)
    {
        if ($this->isEditable()) {
            $html .= '<span type="button" class="btn btn-default btn-small" onclick="';
            $html .= $this->getJsActionOnclick('addFact', array('id' => $id)) . '">';
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . ' Ajouter';
            $html .= '</span>';
            return $html;
        }
    }

    function calc()
    {
        $ln = $this->getData('data');

        $matches = array();
        $price = 0;

        if (preg_match_all('/(FA[A-Z]*[0-9]{4})[\-_ ]?([0-9]{5})/', $ln, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $matche) {
                $this->refs[$matche[1] . '-' . $matche[2]] = $matche[1] . '-' . $matche[2];
            }
        }

        $totalFact = 0;
        foreach ($this->getData('factures') as $idF) {
            $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idF);

            $this->total_reste_a_paye += $obj->getData('remain_to_pay');
            $totalFact += $obj->getData('total_ttc');
        }


        if (($this->getData('price') - $this->total_reste_a_paye) < 0.10 /*&& ($this->getData('price') - $this->total_reste_a_paye) > -0.10*/)
            $this->ok = true;
        if($totalFact - $this->getData('price') < 0.10 && $totalFact - $this->getData('price') > -0.10)
            $this->ok = true;
        if ($this->getData('traite'))
            $this->ok = true;
    }

    function fetch($id, $parent = null)
    {
//        global $modeCSV; $modeCSV = true;
        $return = parent::fetch($id, $parent);
        if ($this->isLoaded())
            $this->calc();
        return $return;
    }

    function actionAddFact($data)
    {
        $errors = $warnings = array();
        $this->addFact($data['id']);
        return array('errors' => $errors, 'warnings' => $warnings);
    }
    
    function actionCreateAcompte($data, &$success)
    {
        $errors = $warnings = array();
        $success = 'Acompte créer';
        $idFacture = 0;
        
        $parent = $this->getParentInstance();
        
        if($data['object_type'] == 0){//pas encore géré
            if($data['id_client'] > 0){
                $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $data['id_client']);
                if(!$client->isLoaded())
                    $errors[] = 'Client introuvable';
            }
            else
                $errors[] = 'Pas de client séléctionné';
        }
        elseif($data['object_type'] == 1){
            if($data['id_propal'] > 0){
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', $data['id_propal']);

                if(!$obj->isLoaded())
                    $errors[] = 'Propal introuvable';
                $linkedObj = getElementElement('propal', 'commande', $obj->id);
                if(count($linkedObj)){
                    $errors[] = 'Une commande existe : '.BimpCache::getBimpObjectLink('bimpcommercial', 'Bimp_Commande', $linkedObj[0]['d']);
                }
            }
            else
                $errors[] = 'Pas de propal séléctionné';
            
        }
        elseif($data['object_type'] == 2){
            if($data['id_commande'] > 0){
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $data['id_commande']);
//                    $propal = new Bimp_Propal();
                if(!$obj->isLoaded())
                    $errors[] = 'Commande introuvable';
            }
            else
                $errors[] = 'Pas de commande séléctionné';
            
        }
        if(!count($errors)){
            $client = $obj->getChildObject('client');
            if($obj->getData('total_ttc')+0.1 < $this->getData('price'))
                $errors[] = 'Montant plus grand que le total de la piéce';
            if(!count($errors))
                $errors = $obj->createAcompte($this->getData('price'), $parent->id_mode_paiement, $this->getData('banque'), 1, $this->getData('date'), false, '', '', '', $warnings, 0, $this->getData('num'), $idFacture);
            $obj->addNoteToCommercial('Bonjour.<br/>Le client '.$client->getLink().' a effectué un virement de '.$this->getData('price').' €.');
        }
        
        if(!count($errors) && $idFacture > 0){
            global $user;
            $errors = BimpTools::merge_array($errors, $this->set('factures', array($idFacture)));
            $errors = BimpTools::merge_array($errors, $this->set('traite', 1));
            $errors = BimpTools::merge_array($errors, $this->set('id_user_traite', $user->id));
            
            $errors = BimpTools::merge_array($errors, $this->update());
        }
//        $errors[] = 'fin';
        return array('errors' => $errors, 'warnings' => $warnings, 'success_callback' => '');
    }

    function actionTraiteManuel($data)
    {
        global $user;
        $errors = $warnings = array();
        
        
        $code = $data['raison'];
        $detail = $data['raison_detail'];
        $infos = $this->getData('infos');
        if($infos != '')
            $infos .= '<br/>';
        $infos .= $this->raisonManu[$code];
        
        if($code == 99){
            if(isset($detail) && $detail != '')
                $infos .= ' ('. $data['raison_detail'].')';
            else
                $errors[] = 'Raison obligatoire';
        }
        
        if(!count($errors)){
            $errors = BimpTools::merge_array($errors, $this->set('infos', $infos));
            $errors = BimpTools::merge_array($errors, $this->set('traite', 1));
            $errors = BimpTools::merge_array($errors, $this->set('id_user_traite', $user->id));


            $errors = BimpTools::merge_array($errors, $this->update());
        }
        
        

        return array('errors' => $errors, 'warnings' => $warnings);
    }

    function addFact($ids)
    {
        if (!is_array($ids))
            $ids = array($ids);
        $tab1 = BimpTools::merge_array($this->getData('factures'), $ids);
        $tab2 = array();
        foreach ($tab1 as $id) {
            $tab2[$id] = $id;
        }
        $this->updateField('factures', $tab2);
    }

    function getRowStyle()
    {
        if ($this->ok)
            return 'background-color:green!important;opacity: 0.2;';
    }

    function getFactPossible()
    {
        global $modeCSV;
        $return = array();
        $factIds = array();
        if (!$this->ok && $this->getData('price') > 0) {
            $sql = $this->db->db->query('SELECT SUM(remain_to_pay) as remain_to_pay_tot, fk_soc FROM `llx_facture` WHERE fk_statut = 1 AND paye = 0 GROUP BY fk_soc HAVING remain_to_pay_tot = ' . $this->getData('price'));
            while ($ln = $this->db->db->fetch_object($sql)) {
                $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $ln->fk_soc);
                $facts = array();
                $list = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('paye' => 0, 'fk_soc' => $ln->fk_soc));
                foreach ($list as $fact) {
                    $factIds[] = $fact->id;
                    if ($modeCSV)
                        $facts[] = $fact->getRef();
                    else
                        $facts[] = $fact->getLink() . $this->getButtonAdd($fact->id);
                }
                if ($modeCSV)
                    $return[] = $cli->getData('nom') . ' (' . implode(' - ', $facts) . ')';
                else
                    $return[] = $cli->getLink() . ' (' . implode(' - ', $facts) . ')';
            }
            
            
            $sql = $this->db->db->query('SELECT remain_to_pay, rowid, fk_soc FROM `llx_facture` WHERE fk_statut = 1 AND paye = 0 AND remain_to_pay = ' . $this->getData('price'));
            while ($ln = $this->db->db->fetch_object($sql)) {
                if(!in_array($ln->rowid, $factIds)){
                    $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $ln->fk_soc);
                    $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $ln->rowid);
                    $facts = array();
                    if ($modeCSV)
                        $facts[] = $fact->getRef();
                    else
                        $facts[] = $fact->getLink() . $this->getButtonAdd($fact->id);
                    if ($modeCSV)
                        $return[] = $cli->getData('nom') . ' (' . implode(' - ', $facts) . ')';
                    else
                        $return[] = $cli->getLink() . ' (' . implode(' - ', $facts) . ')';
                }
            }
            
            
        }
        return implode('<br/>', $return);
    }

    function getFactClient($max = 10)
    {
        global $modeCSV;
        $return = array();
        if (!$this->ok && $this->getData('price') > 0 && $this->getData('name') != '') {
            $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe');
            $results = $cli->getSearchResults('default', $this->getData('name'));
            if ($results) {
                foreach ($results as $result) {
                    $total = 0;
                    $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $result['id']);

                    $facts = array();
                    $list = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array('paye' => 0, 'fk_soc' => $cli->id), 'id', 'asc', array(), $max);
                    foreach ($list as $fact) {
                        if ($modeCSV)
                            $facts[] = $fact->getRef();
                        else
                            $facts[] = $fact->getLink() . $this->getButtonAdd($fact->id);
                        $total += $fact->getData('remain_to_pay');
                    }
                    if (count($facts)) {
                        if ($modeCSV)
                            $return[] = $cli->getData('nom') . ' (' . implode(' - ', $facts) . ') Total : ' . price($total);
                        else
                            $return[] = $cli->getLink() . ' (' . implode(' - ', $facts) . ') Total : ' . price($total);
                    }
                    if(count($list) == $max)
                        $return[] = '...';
                }
            } else {
                $return[] = $name;
            }
        }
        return implode('<br/><br/>', $return);
    }

    function getDataInfo()
    {
        return BimpTools::getDataLightWithPopover($this->getData('data'));
    }

    function getFactReconnue()
    {
        $return = array();
        $refs = $this->refs;
        foreach ($refs as $ref) {
            $obj = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array('facnumber' => $ref));
            if ($obj && $obj->isLoaded()) {
                $return[] = $obj;
            }
        }
        return $return;
    }

    function displayFactReconnue()
    {
        $refs = $this->refs;
        global $modeCSV;
        if (!$modeCSV) {
            foreach ($refs as $ref) {
                $obj = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array('facnumber' => $ref));
                if ($obj && $obj->isLoaded()) {
                    $html = $obj->getLink();
                    $html .= $this->getButtonAdd($obj->id);

                    $refs[$ref] = $html;
                }
            }
        }

        return implode(' - ', $refs);
    }

    function isEditable($force_edit = false, &$errors = array()): int
    {
        return !$this->getInitData('traite');
    }

    function getPrice()
    {
        global $modeCSV;
        if ($modeCSV) {
            return $this->getData('price');
        } else {
            if ($this->getData('type') == 'vir') {
                $manque = $this->getData('price') - $this->total_reste_a_paye;
                if ($this->getData('traite') == 0) {
                    return BimpRender::renderAlerts(price($this->getData('price')) . ' - ' . price($this->total_reste_a_paye) . ' = ' . price($manque) . ' €', ($this->ok ? 'success' : 'danger'));
                } else
                    return BimpRender::renderAlerts(price($this->getData('price')) . ' €', ($manque == 0 ? 'success' : 'danger'));
            }
            return 'Non géré';
        }
    }
}
