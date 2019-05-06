<?php

class userController extends BimpController {
    
    public function displayHead()
    {
        
        if(BimpTools::getValue("socid") > 0){
            $this->displayClientHead(BimpTools::getValue("socid"));
        }
//        global $langs;
//        $commande = $this->config->getObject('', 'commande');
//        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
//        $head = commande_prepare_head($commande->dol_object);
//        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
    }
    
    public function displayClientHead($socid){
        global $db, $langs, $user; 
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($socid);
        $head = societe_prepare_head($soc);
        dol_fiche_head($head, 'client_user', $langs->trans("SAV"));
        
        
        $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        dol_banner_tab($soc, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom', '', '&fc=user');
        
    }

    public function renderHtml() {
        global $userClient;
        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $instance = BimpObject::getInstance('bimpinterfaceclient', 'BIC_UserClient');
        
        if(BimpTools::getValue("socid") > 0){
            $list = new BC_ListTable($instance, 'default', 1, null, '', 'far_user');
            $list->addFieldFilterValue('attached_societe', BimpTools::getValue("socid"));
        }
        else{
            
            $list = new BC_ListTable($instance, 'full', 1, null, '', 'far_user');
        }
        $html .= $list->renderHtml();
        $html .= '</div>';

        return $html;
    }

}
