<?php

class userPropalsController extends BimpController
{

    public function displayHead()
    {
//        global $db, $langs, $user; 
//        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
//        $soc = new Societe($db);
//        $soc->fetch($_REQUEST['id']);
//        $head = societe_prepare_head($soc);
//        dol_fiche_head($head, 'supportsav', $langs->trans("SAV"));
//        
//        
//    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
//
//    dol_banner_tab($soc, 'id', $linkback, ($user->societe_id?0:1), 'rowid', 'nom', '', '&fc=client_sav');
////        global $langs;
////        $commande = $this->config->getObject('', 'commande');
////        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
////        $head = commande_prepare_head($commande->dol_object);
////        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
    }

    public function renderHtml()
    {
        global $user;
        $id = $user->id;
        if (BimpTools::isSubmit('id'))
            $id = (int) BimpTools::getValue('id', 0, 'int');

        $userObj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id);

        if (!BimpObject::objectLoaded($userObj)) {
            return BimpRender::renderAlerts('ID du commercial invalide');
        }

        $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');

        if ($userObj->id == $user->id) {
            $titre = 'Vos proposition commerciales';
        } else {
            $titre = "Les propositions de : " . $userObj->getName();
        }
        $list = new BC_ListTable($propal, 'user', 1, null, $titre);
        $list->addFieldFilterValue('ec.fk_socpeople', (int) $userObj->id);
        $list->addFieldFilterValue('tc.element', 'propal');
        $list->addFieldFilterValue('tc.source', 'internal');
        $list->addFieldFilterValue('tc.code', 'SALESREPFOLL');
        $list->addJoin('element_contact', 'ec.element_id = a.rowid', 'ec');
        $list->addJoin('c_type_contact', 'ec.fk_c_type_contact = tc.rowid', 'tc');
        $list->params['add_form_values']['fields']['id_user_commercial'] = (int) $userObj->id;
        return $list->renderHtml();
    }
}
