<?php

class facturesController extends BimpController
{

    var $socid = "";

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
    
    public function display(){
        if (BimpTools::getValue("socid") > 0)
            $this->socid = BimpTools::getValue("socid");
        return parent::display();
    }

    public function renderFacturesTab()
    {
        $list = 'default';
        $titre = 'Factures';
        if ($this->socid) {
            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);

            if (!BimpObject::objectLoaded($societe)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom');
        }

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        $list = new BC_ListTable($facture, $list, 1, null, $titre);


        if ($this->socid) {
            $list->addFieldFilterValue('fk_soc', (int) $societe->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        }
        return $list->renderHtml();
    }

    public function renderProdsTabs()
    {

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

        $bc_list = new BC_ListTable($line, 'global', 1, null, 'Liste des produits en factures', 'fas_bars');
        if ($this->socid) {
            $bc_list->addJoin('facture', 'a.id_obj = parent.rowid', 'parent');
            $bc_list->addFieldFilterValue('parent.fk_soc', $this->socid);
        }

        return $bc_list->renderHtml();
    }

    public function renderPaiementsTab()
    {
        $list = 'default';
        $titre = 'Paiements';
        if ($this->socid) {
            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);

            if (!BimpObject::objectLoaded($societe)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom');
        }

        $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');

        $list = new BC_ListTable($paiement, $list, 1, null, $titre);
        
        if ($this->socid) {
            $list->addJoin('paiement_facture', 'a.rowid = paiement_fact.fk_paiement', 'paiement_fact');
            $list->addJoin('facture', 'paiement_fact.fk_facture = parent.rowid', 'parent');
            $list->addFieldFilterValue('parent.fk_soc', $this->socid);
        }

        
        return $list->renderHtml();
    }
}
