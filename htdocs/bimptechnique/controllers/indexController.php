<?php

class indexController extends BimpController {
    
    public function getDataUser($attr, $if_right = "") {
        global $user;
        $u = BimpObject::getInstance('bimpcore', 'Bimp_User', $user->id);
        switch($attr) {
            case "name": 
                return $u->getName();
                break;
            case "right":
                if($if_right != "") {
                    return $user->rights->bimptechnique->$if_right;
                }
                return 0;
                break;
        }
    }
    
    public function renderTech() {
        global $user;
        $html = "";
        
        if(isset($_REQUEST['specialTech']) && $_REQUEST['specialTech'] > 0)
            $userId = $_REQUEST['specialTech'];
        else
            $userId = $user->id;
        
        $tech = BimpObject::getInstance('bimpcore', "Bimp_User", $userId);
        
        $your_fiches = BimpObject::getInstance('bimptechnique', 'BT_ficheInter');
        $list = new BC_ListTable($your_fiches, "tech");
        $list->setParam('n', 5);
        $buttons[] = array(
            'label' => 'Planifier une intervention',
            'icon' => 'fas_calendar',
            'onclick' => $your_fiches->getJsActionOnclick('createFromRien', array(), array(
                'form_name' => 'createFiche'
            ))
        );
        
        if($user->id != $tech->id) {
            $html .= "<h3><strong class='warning' >".BimpRender::renderIcon('warning')."</strong><strong>Vous regardez les intervention de </strong><strong class='warning'>".$tech->getName()."</strong></h3>";
        }
        
        $html .= BimpRender::renderButtonsGroup($buttons, []);
        $html .= $list->renderHtml();
        
        $list = new BC_ListTable($your_fiches, 'historiqueTech');
        $list->setParam('title', "Liste des fiches d'interventions de l'ancien module");
        $list->setParam('n', 5);
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
    
    public function renderStats() {
        $html = "";
        // &navtab-maintabs=NOM_TAB
        if($this->getDataUser("right", "view_stats")) {
            $mois_rapport = date('m');
            $annee_rapport = date('Y');
            if(isset($_REQUEST['moisRapport']) && $_REQUEST['moisRapport'] > 0) {
                $mois_rapport = $_REQUEST['moisRapport'];
            }
            if(isset($_REQUEST['anneeRapport']) && $_REQUEST['anneeRapport'] > 0) {
                $annee_rapport = $_REQUEST['anneeRapport'];
            }
            
            $current = new DateTime($annee_rapport . "-" . $mois_rapport . "-01");
            
            $html .= "<h3><b style='color:#EF7D00'>BIMP</b><b>technique</b> <sup style='color:grey' >Statistiques</sup>";
            
            switch($current->format('m')) {
                case "01":$m = "Janvier";break;
                case "02":$m = "Février";break;
                case "03":$m = "Mars";break;
                case "04":$m = "Avril";break;
                case "05":$m = "Mai";break;
                case "06":$m = "Juin";break;
                case "07":$m = "Juillet";break;
                case "08":$m = "Aout";break;
                case "09":$m = "Septembre";break;
                case "10":$m = "Octobre";break;
                case "11":$m = "Novembre";break;
                case "12":$m = "Décembre";break;
            }

            $m_moin_un = (int) $current->format('m') - 1;
            $m_plus_un = (int) $current->format('m') + 1;
                        
            $html .= "<b style='margin-left:50px; font-size:15px'>".BimpRender::renderIcon("arrow-left", "backButton")."<b style='font-size:20px; margin-left:20px; margin-right: 20px'>".$m . " " . $current->format('Y') . "</b>".BimpRender::renderIcon('arrow-right')."</b>";
            
            $html .= "</h3><hr>";
            
        } else {
            $html .= BimpRender::renderAlerts("Vous n'avez pas la permission de voir les statistiques des fiches d'interventions - EN DEV", "danger", false);
        }

        return $html;
    }
    
    protected function ajaxProcessChargeStat() {
        
    }
    
}