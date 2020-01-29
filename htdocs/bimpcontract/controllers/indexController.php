<?php
class indexController extends BimpController
{
    public function displayHead()
    {
        global $db, $langs, $user;
        
        if (BimpTools::getValue("socid") > 0) {
            $this->socid = BimpTools::getValue("socid");
            require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
            $soc = new Societe($db);
            $soc->fetch($this->socid);
            $head = societe_prepare_head($soc);
            dol_fiche_head($head, 'bimpcomm', '');

            $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

            dol_banner_tab($soc, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=factures');
        }
    }
    
    public function renderContractsTab()
    {
        $this->getSocid();
        $list = 'default';
        $titre = 'Contrats';
        if ($this->socid) {
            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);

            if (!BimpObject::objectLoaded($societe)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom');
        }
        
        $obj = BimpObject::getInstance('bimpcontract', 'BContract_contrat');

        $list = new BC_ListTable($obj, $list, 1, null, $titre);


        if ($this->socid) {
            $list->addFieldFilterValue('fk_soc', (int) $societe->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        }
        return $list->renderHtml();
    }
    
    public function renderTimeTableTab() {
        $this->getSocid();
        $list = "default";
        $titre = 'Echéanciers';
        
        if($this->socid) {
            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);
            if (!BimpObject::objectLoaded($societe)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom');
        }
        
        $obj = BimpObject::getInstance('bimpcontract', 'BContract_echeancier');

        $list = new BC_ListTable($obj, $list, 1, null, $titre);
        
        if ($this->socid) {
            $contrats = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_contrat');
            $liste_contrats = $contrats->getList(['fk_soc' => $this->socid]);
            
            
            $for_filter = [];
            foreach($liste_contrats as $contrat => $field) {
                $for_filter[] =  $field['rowid'];
            }
            $list->addFieldFilterValue('id_contrat', ['IN' => $for_filter]);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        }
        
        return $list->renderHtml();
    }
    
    public function getSocid(){
        if($this->socid < 1){
            if (BimpTools::getValue("socid") > 0) {
                $this->socid = BimpTools::getValue("socid");
                $this->soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);
            }
        }
    }
    

}
