<?php

class Bimpcoopmvt extends BimpObject
{
    public function renderCoopUserView($idUser){
        
        global $user;

//        if ($user->admin || $user->id === $this->id) {

            $tabs[] = array(
                'id'            => 'lists_capital_tab',
                'title'         => 'Capital',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderListObjects', '$(\'#lists_capital_tab .nav_tab_ajax_result\')', array($idUser, 1), array('button' => ''))
            );

            $tabs[] = array(
                'id'            => 'lists_cca_tab',
                'title'         => 'CCA',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderListObjects', '$(\'#lists_cca_tab .nav_tab_ajax_result\')', array($idUser, 2), array('button' => ''))
            );

            return BimpRender::renderNavTabs($tabs, 'params_tabs');
//        }

        return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
    }
    
    public function renderStat(){
        global $db;
        $html = '';
        $rows = array(1=>array(), 2=>array());
        $totals = array(1=>0, 2=>0);
        
        //
        
        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt GROUP BY fk_user, type;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $rows[$ln->type][] = array(
                    'name' => $userT->getFullName(),
                    'montant' => array(
                        'value' => $ln->value,
                        'content' => BimpTools::displayMoneyValue($ln->value),
                    ),
                );
            }
        }
        
        
        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt GROUP BY type;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $totals[$ln->type] = array(
                    'value' => $ln->value,
                    'content' => BimpTools::displayMoneyValue($ln->value)
                );
            }
        }
//        echo '<pre>';
//        print_r($rows);die;
        
        
        $header = array(
            'name'     => 'Nom',
            'montant'   => 'Value'
        );
        
        
        $html .= BimpRender::renderBimpListTable(array(0=>array(
            'cap' => BimpRender::renderBimpListTable($rows[1], $header, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
                )),
            'cca' => BimpRender::renderBimpListTable($rows[2], $header, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
            ))
        ),1=>array(
            'cap'=>$totals[1],
            'cca'=>$totals[2]     
        )
        ), array('cap'=>'Capital', 'cca'=>'Compte courant'));
        
        return $html;
    }
    
    public function renderListObjects($userId, $type){
        $list = new BC_ListTable($this, 'default', 1, null, 'Mouvements', 'fas_users');
        $list->addFieldFilterValue('type', $type);
        $list->addFieldFilterValue('fk_user', $userId);
        $list->addIdentifierSuffix('type_'.$type);
        return $list->renderHtml();
    }
    
    public function displayPaiement(){
        if($this->getData('id_paiement')){
            $paiement = $this->getChildObject('paiementDiv');
            return $paiement->getNomUrl(1);
        }
        else{
            return BimpRender::renderButton(array(
                'label'   => 'Créer paiement',
                'icon'    => 'far_paper-plane',
                'onclick' => $this->getJsActionOnclick('create_paiement', array(), array('form_name' => 'create_paiement'))
            ));
        }
        return 'bouton';
    }
    
    public function getListsExtraBulkActions(){
        $buttons = array();
        $buttons[] = array(
                'label'   => 'Crées paiements',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsBulkActionOnclick('create_paiement', array(), array('form_name' => 'create_paiement'))
            );
        return $buttons;
    }
    
    public function actionCreate_paiement($data, &$success = ''){
        global $user;
        $success = 'Paiement créer avec succés';
        $errors = array();
        
        
        if($this->isLoaded()){
            $objs = array($this);
        }
        else{
            $objs = array();
            foreach (BimpTools::getArrayValueFromPath($data, 'id_objects', array()) as $id){
                $objs[] = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id);
            }
        }
             
        $amount = 0;
        $userM = null;
        $paiement = $this->getChildObject('paiementDiv');
        $type = null;
        $notes = array();
        foreach($objs as $obj){
            if(is_null($userM))
                $userM = $obj->getChildObject('userM');
//            elseif($userM != $obj->getChildObject('userM'))
//                $errors[] = 'PLusieurs utilisateurs diférent';
            $amount += $obj->getData('value');
            if(is_null($paiement->datep))
                $paiement->datep = $obj->getData('date');
            elseif($paiement->datep != $obj->getData('date'))
                $errors[] = 'PLusieurs date diférente';
            if(is_null($type))
                $type = $obj->displayData('type', null, null, true);
            elseif($type != $obj->displayData('type', null, null, true))
                $errors[] = 'PLusieurs type diférent';
            $notes[] = $obj->getData('note');
            $paiementTemp = $obj->getChildObject('paiementDiv');
            if($paiementTemp->id > 0)
                $errors[] = 'La ligne comporte deja un paiement';
        }
        $paiement->amount = abs($amount);
        $paiement->sens = ($amount > 0)? 1 : 0;
        $paiement->label = $type.' '.$userM->getFullName();
        $paiement->fk_account = $data['id_account'];
        $paiement->type_payment = $data['id_mode_paiement'];
        $paiement->accountancy_code = '422';
        $paiement->subledger_account = $userM->getData('code_compta');
        $paiement->note = implode('\n', $notes);
        
        if($paiement->create($user) < 1)
            $errors[] = 'erreur '.$paiement->error;
        else{
            foreach ($objs as $obj){
                $obj->updateField('id_paiement', $paiement->id);
            }
        }
        
        return array('errors'=> $errors, 'warnings'=>array());
    }
    
    public function isEditable($force_edit = false, &$errors = []): int {
        $paiementTemp = $this->getChildObject('paiementDiv');
        if($paiementTemp->id > 0)
            return 0;
        
        
        return parent::isEditable($force_edit, $errors);
    }
    
    public function isDeletable($force_delete = false, &$errors = []) {
        return $this->isEditable($force_delete, $errors);
    }
    
    
    //graph
    public function getFieldsGraphRep($type = 1, $label = ''){
        $fields = array();
        $filter = array(
            'type'      => $type
        );
        if($label != '')
            $filter['info'] = 'URGENCE';
        $cmds = BimpCache::getBimpObjectObjects($this->module, $this->object_name, $filter);
        foreach($cmds as $cmdData){
            $userM = $cmdData->getChildObject('userM');
            if($userM->isLoaded())
                $title = $userM->getFullName();
            else
                $title = 'n/c';
            
            $filter2 = array_merge($filter, array('fk_user' => $userM->id));
            $fields[$userM->id] = array(
               "title"      => $title,
               'field'     => 'value',
               'calc'      => 'SUM',
               'filters'    => $filter2
            );
        }
        return $fields;
    }
}




