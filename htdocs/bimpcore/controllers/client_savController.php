<?php

class client_savController extends BimpController
{

    public function displayHead()
    {
        global $db, $langs, $user; 
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($_REQUEST['id']);
        $head = societe_prepare_head($soc);
        dol_fiche_head($head, 'supportsav', $langs->trans("SAV"));
        
        
    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($soc, 'id', $linkback, ($user->societe_id?0:1), 'rowid', 'nom', '', '&fc=client_sav');
//        global $langs;
//        $commande = $this->config->getObject('', 'commande');
//        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
//        $head = commande_prepare_head($commande->dol_object);
//        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
    }

    public function renderHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID du client absent');
        }
        
        $return = '';

        $client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) BimpTools::getValue('id', 0));

        if (!BimpObject::objectLoaded($client)) {
            return BimpRender::renderAlerts('ID du client invalide');
        }

        $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');

        $list = new BC_ListTable($sav, 'default', 1, null, 'SAV enregistrés pour ce client', 'wrench');
        $list->addFieldFilterValue('id_client', (int) $client->id);
        $return .= $list->renderHtml();
        
        
        $ticket = BimpObject::getInstance('bimpsupport', 'BS_Ticket');

        $list = new BC_ListTable($ticket, 'default', 1, null, 'Ticket enregistrés pour ce client', 'wrench');
        $list->addFieldFilterValue('id_client', (int) $client->id);
        $return .= $list->renderHtml();
        
        
        $pret = BimpObject::getInstance('bimpsupport', 'BS_Pret');

        $list = new BC_ListTable($pret, 'default', 1, null, 'Pret enregistrés pour ce client', 'wrench');
        $list->addFieldFilterValue('id_client', (int) $client->id);
        $return .= $list->renderHtml();
        return $return;
    }
}
