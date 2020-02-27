<?php

class propalsController extends BimpController
{
    var $socid = "";

    public function displayHead()
    {
        global $db, $langs, $user;
        
        if(BimpTools::getValue("socid") > 0){
            $this->socid = BimpTools::getValue("socid");
            require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
            $soc = new Societe($db);
            $soc->fetch($this->socid);
            $head = societe_prepare_head($soc);
            dol_fiche_head($head, 'bimpcomm', '');
            
            $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

            dol_banner_tab($soc, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=clientPropals');
        }
    }

    public function renderHtml()
    {
        $list = 'default';
        $titre = 'Proposition commerciales';
        $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
        if($this->socid){
            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);

            if (!BimpObject::objectLoaded($societe)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom');
        }
        
        if(isset($_REQUEST['fk_statut'])){
            if(isset($propal::$status_list[GETPOST('fk_statut')]))
            $titre .= ' au statut '.$propal::$status_list[GETPOST('fk_statut')]['label'];
        }
        
        


        $list = new BC_ListTable($propal, $list, 1, null, $titre);
        
        
        if($this->socid){
            $list->addFieldFilterValue('fk_soc', (int) $societe->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        }
        
        if(isset($_REQUEST['fk_statut'])){
            $list->addFieldFilterValue('fk_statut', (int) GETPOST('fk_statut'));
        }
            return $list->renderHtml();
    }
}
