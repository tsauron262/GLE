<?php

class clientPropalsController extends BimpController
{

    public function displayHead()
    {
        global $db, $langs, $user;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($_REQUEST['id']);
        $head = societe_prepare_head($soc);
        dol_fiche_head($head, 'bimpcomm', '');


        $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        dol_banner_tab($soc, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=clientPropals');
    }

    public function renderHtml()
    {
        
//        $obj = BimpObject::getInstance('bimpsupport', 'BS_SAV',864);
//        
//        echo "<pre>";print_r($obj->getExport());die;
        
        
        
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID du client absent');
        }

        $societe = BimpObject::getInstance('bimpcore', 'Bimp_Societe', (int) BimpTools::getValue('id', 0));

        if (!BimpObject::objectLoaded($societe)) {
            return BimpRender::renderAlerts('ID du client invalide');
        }

        $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');

        $list = new BC_ListTable($propal, 'client', 1, null, 'Proposition commerciales du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom') . '"');
        $list->addFieldFilterValue('fk_soc', (int) $societe->id);
        $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        return $list->renderHtml();
    }
}
