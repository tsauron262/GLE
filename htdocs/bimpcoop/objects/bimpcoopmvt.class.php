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
    
    public function renderCapitalCCA(){
        global $db;
        $panels = array();
        $rows = array(1=>array(), 2=>array());
        $totals = array(1=>0, 2=>0);
        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt GROUP BY fk_user, type;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $rows[$ln->type][] = array(
                    'name' => $userT->getFullName(),
                    'montant' => BimpTools::displayMoneyValue($ln->value),
                );
            }
        }
        
        
        $sql = $db->query('SELECT SUM(value) as value, fk_user, type FROM '.MAIN_DB_PREFIX.'bimp_coop_mvt GROUP BY type;');
        while ($ln = $db->fetch_object($sql)){
            $userT = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $ln->fk_user);
            if($ln->value != 0){
                $totals[$ln->type] = BimpTools::displayMoneyValue($ln->value);
            }
        }
//        echo '<pre>';
//        print_r($rows);die;
        
        
        $header = array(
            'name'     => 'Nom',
            'montant'   => 'Value'
        );
        
        $panels['Capital'] = array('content'=>BimpRender::renderBimpListTable($rows[1], $header, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
                )).'TOTAL : '.$totals[1], 'xs'=>12,'sm'=>12,'md'=>6);
        
        
        $panels['CCA'] = array('content'=>BimpRender::renderBimpListTable($rows[2], $header, array(
                            'searchable'  => true,
                            'sortable'    => true,
                            'search_mode' => 'show'
            )).'TOTAL : '.$totals[2], 'xs'=>12,'sm'=>12,'md'=>6);
        
        return $panels;
    }
    
    public function renderStat(){
        global $db;
        $panels = array();
        
        
        $tabInfoSolde = array();
        
        
        //bank
        $sql = $db->query("SELECT * FROM `".MAIN_DB_PREFIX."bank_account`;");
        $bank  = array();
        while($ln = $db->fetch_object($sql)){
            $bank[$ln->rowid] = $ln->label;
        }
        
//        $html .= BimpRender::renderPanel('Chiffres ', $content, '', array('open' => 1));
        //47000
        
        
        
        
        
        //categorie
        $sql = $db->query("SELECT * FROM `".MAIN_DB_PREFIX."bimp_c_values8sens` WHERE `type` = 'categorie';");
        $categ  = array();
        while($ln = $db->fetch_object($sql)){
            $categ[$ln->id] = $ln->label;
        }
        
        
        //Recette (stat vente)
        $tabInfoR = array();
        $sql = $db->query('SELECT a_product_ef.categorie AS categorie,  SUM( CASE WHEN f.fk_statut IN ("1","2") THEN a.total_ttc ELSE 0 END) AS tot
FROM '.MAIN_DB_PREFIX.'facturedet a
LEFT JOIN '.MAIN_DB_PREFIX.'facture f ON f.rowid = a.fk_facture
LEFT JOIN '.MAIN_DB_PREFIX.'facture a___parent ON a___parent.rowid = a.fk_facture
LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields a_product_ef ON a_product_ef.fk_object = a.fk_product
WHERE a___parent.type IN ("0","1","2")'.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND f.datef > "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND f.datef < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
' GROUP BY categorie
ORDER BY a.rowid DESC;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoR[$label] = $ln->tot;
            }
        }
        if(!BimpTools::getPostFieldValue('dateD', null) && !BimpTools::getPostFieldValue('dateF', null)){
            $tabInfoR['Location'] += BimpCore::getConf('b_loyer', 0, 'bimpcoop');
        }
        
        $sql = $db->query('SELECT SUM(amount_ttc) as tot FROM `'.MAIN_DB_PREFIX.'societe_remise_except` WHERE (fk_facture < 0 OR fk_facture IS NULL) AND fk_facture_source > 0'.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND datec > "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND datec < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
'');
        while($ln = $db->fetch_object($sql)){
            $tabInfoR['Acompte'] += $ln->tot;
        }
        
        
        
        //depensse (stat achat)
        $tabInfoD = array();
        $sql = $db->query('SELECT a_product_ef.categorie AS categorie, SUM( CASE WHEN f.fk_statut IN ("1","2") THEN a.total_ttc ELSE 0 END) AS tot
FROM '.MAIN_DB_PREFIX.'facture_fourn_det a
LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn f ON f.rowid = a.fk_facture_fourn
LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields a_product_ef ON a_product_ef.fk_object = a.fk_product
WHERE 1'.
                (BimpTools::getPostFieldValue('dateD', null)? ' AND f.datef > "'.BimpTools::getPostFieldValue('dateD').'" ':'').
                (BimpTools::getPostFieldValue('dateF', null)? ' AND f.datef < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
'
GROUP BY categorie;');
        while($ln = $db->fetch_object($sql)){
            if($ln->tot != 0){
                $label = $categ[$ln->categorie];
                if($label == '')
                    $label = 'Categ inconnue '.$ln->categorie;
                $tabInfoD[$label] = $ln->tot;
            }
        }
        if(!BimpTools::getPostFieldValue('dateD', null) && !BimpTools::getPostFieldValue('dateF', null)){
            $tabInfoD['Travaux'] += BimpCore::getConf('b_travaux', 0, 'bimpcoop');
            $tabInfoD['Divers'] += BimpCore::getConf('b_autre', 0, 'bimpcoop');
        }
        
        
        
        
        
        
        
        $sql = $db->query('SELECT SUM(amount)as solde, fk_account FROM '.MAIN_DB_PREFIX.'bank'
                . 'WHERE '.
                (BimpTools::getPostFieldValue('dateF', null)? ' AND datev < "'.BimpTools::getPostFieldValue('dateF').'" ':'').
                 ' GROUP BY fk_account');
        $tot = 0;
        while($ln = $db->fetch_object($sql)){
            if($ln->solde != 0){
                $label = $bank[$ln->fk_account];
                if($label == '')
                    $label = 'Banque '.$ln->fk_account;
                $tabInfoSolde['Solde '.$label] = $ln->solde;
                $tot += $ln->solde;
            }
        }
        if(GETPOST('ajPret') == 1){
            $tabInfoSolde['Solde Banque POP'] -= 1446.38;
            $tot -= 1446.38;
            $tabInfoSolde['Solde NEF'] -= 1430.73;
            $tot -= 1430.73;
        }
        
        if(!BimpTools::getPostFieldValue('dateF', null)){
            $tabInfoSolde['Solde Bl'] = BimpCore::getConf('b_solde', 0, 'bimpcoop');
            $tot += BimpCore::getConf('b_solde', 0, 'bimpcoop');
        }
        $tabInfoSolde[''] = '';
        $tabInfoSolde['TOTAL'] = $tot;
        $tabInfoSolde[' '] = '';
        $tabInfoSolde['DEPUIS DEBUT'] = $tot - 47000;
        $tabInfoSolde['  '] = '';
        
        if(!BimpTools::getPostFieldValue('dateF', null)){
            $tabInfoSolde['Dif Prévi'] = $tot - 10000 + $tabInfoD['Travaux'] - 9000 - BimpCore::getConf('b_previ', 0, 'bimpcoop');
        }
        
        
        
        
        $contentSolde = '<table class="bimp_list_table">';
        foreach($tabInfoSolde as $nom => $val){
            $contentSolde .= '<tr><th>'.$nom.'</th><td>'.BimpTools::displayMoneyValue($val).'</td></tr>';
        }
        $contentSolde .= '</table>';
        $panels['Compta']['Paramétres'] = array('content'=>'<form method="POST">'.BimpInput::renderInput('date', 'dateD', BimpTools::getPostFieldValue('dateD')).BimpInput::renderInput('date', 'dateF', BimpTools::getPostFieldValue('dateF')).'<input type="submit" value="Valider" /></form>', 'xs'=>12,'sm'=>12,'md'=>12);
        $panels['Compta']['Soldes'] = $contentSolde;
        
        $panels['Compta']['Recette'] = $this->traiteTab($tabInfoR);
        $panels['Compta']['Dépences'] = $this->traiteTab($tabInfoD);
        
        
        
        $panels['Repartition Capital CCA'] = array('content'=>$this->renderCapitalCCA(), 'xs'=>12,'sm'=>8,'md'=>8);
        
        
        return $this->renderPanels($panels);
    }
    
    public function renderPanels($panels, $xsD=6, $smD=4, $mdD=4, $open = 1){
        $html = '';
        foreach($panels as $name => $content){
            $xs = $xsD;
            $sm = $smD;
            $md = $mdD;
            if(is_array($content)){
                if(isset($content['xs']))
                    $xs = $content['xs'];
                if(isset($content['sm']))
                    $sm = $content['sm'];
                if(isset($content['md']))
                    $md = $content['md'];
                if(isset($content['content'])){
                    if(is_array($content['content']))
                        $content = $this->renderPanels($content['content']);
                    else
                        $content = $content['content'];
                }
                else{
                    $content = $this->renderPanels($content);
                    $xs = $sm = $md = 12;
                }
            }
//            else
                $content = BimpRender::renderPanel($name, $content, '', array('open' => $open));
            
            $html .= '<div class="col_xs-'.$xs.' col-sm-'.$sm.' col-md-'.$md.'">'.$content.'</div>';
        }
        return $html;
    }
    
    public function traiteTab($tab){
        $tot = 0;
        foreach($tab as $nom => $val){
            $tot += $val;
        }
        $tab[''] = '';
        $tab['TOTAL'] = $tot;
        $content = '<table class="bimp_list_table">';
        foreach($tab as $nom => $val){
            $content .= '<tr><th>'.$nom.'</th><td>'.BimpTools::displayMoneyValue($val).'</td></tr>';
        }
        $content .= '</table>';
        return $content;
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




