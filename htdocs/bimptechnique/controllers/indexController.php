<?php

class indexController extends BimpController
{

    public function renderTech()
    {
        global $user;
        $html = '';

        $fi_instance = BimpObject::getInstance('bimptechnique', 'BT_ficheInter');
        $list = new BC_ListTable($fi_instance, "tech");

        $title = 'Fiches d\'intervention';
        $userId = (int) BimpTools::getValue('specialTech', $user->id, 'int');
        $tech = BimpCache::getBimpObjectInstance('bimpcore', "Bimp_User", $userId);
        
        if (BimpObject::objectLoaded($tech)) {
            $list->addFieldFilterValue('fk_user_tech', $userId);
            $title .= ' assignées à ' . $tech->getName();
        }

        $list->setParam('title', $title);
        $html .= $list->renderHtml();

        $list = new BC_ListTable($fi_instance, 'historique');
        $title = 'Fiches d\'intervention';

        if (BimpObject::objectLoaded($tech)) {
            $list->addFieldFilterValue('fk_user_tech', $userId);
            $title .= ' assignées à ' . $tech->getName();
        }
        
        $title .= ' (ancienne version)';
        $list->setParam('title', $title);
        $html .= $list->renderHtml();

        if ($user->rights->bimptechnique->view_stats) {
            if (isset($_REQUEST['annee']) && $_REQUEST['annee'] != "") {
                $annee = $_REQUEST['annee'];
            } else {
                $annee = date('Y');
            }

            $n_moins_1 = (int) ($annee - 1);
            $n_plus_1 = (int) ($annee + 1);

            $callback_previous = DOL_URL_ROOT . "/bimptechnique/?annee=" . $n_moins_1;
            $callback_next = DOL_URL_ROOT . "/bimptechnique/?annee=" . $n_plus_1;

            if ($n_plus_1 == date('Y')) {
                $callback_next = DOL_URL_ROOT . "/bimptechnique/";
            }

            $html .= "<center><h3><a style='margin-right:50px' href='" . $callback_previous . "'>" . BimpRender::renderIcon('arrow-left') . "</a>";
            $html .= "$annee";
            $html .= "<a style='margin-left:50px' href='" . $callback_next . "'>" . BimpRender::renderIcon('arrow-right') . "</a></h3></center>";
        }

        return $html;
    }

    public function renderStats()
    {
        global $user;

        $html = "";
        // &navtab-maintabs=NOM_TAB

        if ($user->rights->bimptechnique->view_stats) {
            $mois_rapport = date('m');
            $annee_rapport = date('Y');
            if (isset($_REQUEST['moisRapport']) && $_REQUEST['moisRapport'] > 0) {
                $mois_rapport = $_REQUEST['moisRapport'];
            }
            if (isset($_REQUEST['anneeRapport']) && $_REQUEST['anneeRapport'] > 0) {
                $annee_rapport = $_REQUEST['anneeRapport'];
            }

            $current = new DateTime($annee_rapport . "-" . $mois_rapport . "-01");

            $html .= "<h3><b style='color:#EF7D00'>BIMP</b><b>technique</b> <sup style='color:grey' >Statistiques</sup>";

            $a_moin_un = (int) $current->format('Y') - 1;
            $a_plus_un = (int) $current->format('Y') + 1;

            $html .= "<b style='margin-left:50px; font-size:15px'>" . BimpRender::renderIcon("arrow-left", "backButton") . "<b style='font-size:20px; margin-left:20px; margin-right: 20px'>" . $current->format('Y') . "</b>" . BimpRender::renderIcon('arrow-right') . "</b>";

            $html .= "</h3><hr>";
        } else {
            $html .= BimpRender::renderAlerts("Vous n'avez pas la permission de voir les statistiques des fiches d'interventions - EN DEV", "danger", false);
        }

        return $html;
    }

    protected function ajaxProcessChargeStat()
    {
        
    }
}
