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
    
    public function actionCreate_paiement($data, &$success = ''){
        global $user;
        $success = 'Paiement créer avec succés';
        $errors = array();
        
        $userM = $this->getChildObject('userM');
        $paiement = $this->getChildObject('paiementDiv');
        $paiement->amount = abs($this->getData('value'));
        $paiement->sens = ($this->getData('value') > 0)? 1 : 0;
        $paiement->datep = $this->getData('date');
        $paiement->label = $this->displayData('type', null, null, true).' '.$userM->getFullName();
        $paiement->fk_account = $data['id_account'];
        $paiement->type_payment = $data['id_mode_paiement'];
        $paiement->accountancy_code = '422';
        $paiement->subledger_account = $userM->getData('code_compta');
        $paiement->note = $this->getData('note');
        
        if($paiement->create($user) < 1)
            $errors[] = 'erreur '.$paiement->error;
        else{
            $this->updateField('id_paiement', $paiement->id);
        }
        
        return array('errors'=> $errors, 'warnings'=>array());
    }
}




