<?php

class indexController extends BimpController {
    
    public function getDataUser($attr) {
        global $user;
        switch($attr) {
            case "name": 
                $u = BimpObject::getInstance('bimpcore', 'Bimp_User', $user->id);
                return $u->getName();
                break;
        }
    }
    
    public function renderTech() {
        global $user;
        $html = "";
        
        
        
        
        $tech = BimpObject::getInstance('bimpcore', "Bimp_User", $user->id);
        
        $your_fiches = BimpObject::getInstance('bimptechnique', 'BT_ficheInter');
        $list = new BC_ListTable($your_fiches, "tech");
        
        $buttons[] = array(
                            'label' => 'Plannifier une intervention',
                            'icon' => 'fas_calendar',
                            'onclick' => $your_fiches->getJsActionOnclick('createFromRien', array(), array(
                                'form_name' => 'createFiche'
                            ))
                    );
        
        $html .= BimpRender::renderButtonsGroup($buttons, []);
        $html .= $list->renderHtml();
        
        if(isset($_REQUEST['annee']) && $_REQUEST['annee'] != "") {
            $annee = $_REQUEST['annee']; 
        } else {
            $annee = date('Y');
        }
        
        $n_moin_1 = (int) ($annee - 1);
        $n_plus_1 = (int) ($annee + 1);
        
        $callback_previous = DOL_URL_ROOT."/bimptechnique/?annee=" . $n_moin_1;
        $callback_next = DOL_URL_ROOT."/bimptechnique/?annee=" . $n_plus_1;
        
        if($n_plus_1 == date('Y')) {
            $callback_next = DOL_URL_ROOT."/bimptechnique/";
        } 
        
        $html .= "<center><h3><a style='margin-right:50px' href='".$callback_previous."'>".BimpRender::renderIcon('arrow-left')."</a>";
        $html .= "$annee";
        $html .= "<a style='margin-left:50px' href='".$callback_next."'>".BimpRender::renderIcon('arrow-right')."</a></h3></center>";
        return $html;
    }
    
}