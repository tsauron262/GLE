<?php

class commandesFournController extends BimpController
{
    var $socid = "";
    var $soc = null;

    public function displayHead()
    {
        global $db, $langs, $user;

        $this->getSocid();
        
        if($this->socid > 0){
            require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
            $head = societe_prepare_head($this->soc->dol_object);
            dol_fiche_head($head, 'bimpcomm', '');

            $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

            dol_banner_tab($this->soc->dol_object, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=commandes');
        }
    }
    
    public function getSocid(){
        if($this->socid < 1){
            if (BimpTools::getValue("socid") > 0) {
                $this->socid = BimpTools::getValue("socid");
                $this->soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);
            }
        }
    }
    
    public function renderProdsTab()
    {
        $this->getSocid();
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeFournLine');
//        Bimp_CommandeFournLine::checkAllQties();
        
        //        $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

        $bc_list = new BC_ListTable($line, 'general', 1, null, 'Liste des produits en commande', 'fas_bars');
        $bc_list->addJoin('commande_fournisseur', 'a.id_obj = parent.rowid', 'parent');
        $bc_list->addFieldFilterValue('parent.fk_statut', array(
            'operator' => '>=',
            'value'    => 3
        ));

//        if ($id_entrepot) {
//            $bc_list->addJoin('commande_extrafields', 'a.id_obj = cef.fk_object', 'cef');
//            $bc_list->addFieldFilterValue('cef.entrepot', $id_entrepot);
//        }
        if ($this->socid) {
            $bc_list->addFieldFilterValue('parent.fk_soc', (int) $this->socid);
            //$list->params['add_form_values']['fields']['fk_soc'] = (int) $this->soc->id;
        }

        return $bc_list->renderHtml();
    }
    
    
    public function renderCommandesTab()
    {
        $this->getSocid();
//        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeLine');
//        Bimp_CommandeLine::checkAllQties();
        
        $list = 'default';
        $titre = 'Commandes';
        if ($this->socid) {
            if (!BimpObject::objectLoaded($this->soc)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            //$list = 'client';
            $titre .= ' du fournisseur ' . $this->soc->getData('code_fournisseur') . ' - ' . $this->soc->getData('nom');
        }

        $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');

        $list = new BC_ListTable($propal, $list, 1, null, $titre);

        if ($this->socid) {
            $list->addFieldFilterValue('fk_soc', (int) $this->soc->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $this->soc->id;
        }

        return $list->renderHtml();
    }
    
    
    public function renderReceptionsTab()
    {
        $this->getSocid();
//        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeLine');
//        Bimp_CommandeLine::checkAllQties();
        
        $list = 'default';
        $titre = 'Recepetions';
        if ($this->socid) {
            if (!BimpObject::objectLoaded($this->soc)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            //$list = 'client';
            $titre .= ' du client ' . $this->soc->getData('code_client') . ' - ' . $this->soc->getData('nom');
        }

        $propal = BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception');

        $list = new BC_ListTable($propal, $list, 1, null, $titre);

        if ($this->socid) {
            $list->addJoin('commande_fournisseur', 'a.id_commande_fourn = parent.rowid', 'parent');
            $list->addFieldFilterValue('parent.fk_soc', (int) $this->socid);
            //$list->params['add_form_values']['fields']['fk_soc'] = (int) $this->soc->id;
        }

        return $list->renderHtml();
    }


}
