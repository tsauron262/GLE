<?php

class facturesFournController extends BimpController
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
            $list = 'fourn';
            $titre .= ' du fournisseur "' . $societe->getData('code_fournisseur') . ' - ' . $societe->getData('nom');
        }

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn');

        $list = new BC_ListTable($facture, $list, 1, null, $titre);


        if ($this->socid) {
            $list->addFieldFilterValue('fk_soc', (int) $societe->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        }
        return $list->renderHtml();
    }

    public function renderProdsTabs()
    {

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');

        $bc_list = new BC_ListTable($line, 'global', 1, null, 'Liste des produits en factures', 'fas_bars');
        if ($this->socid) {
            $bc_list->addJoin('facture_fourn', 'a.id_obj = parent.rowid', 'parent');
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

        $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_PaiementFourn');

        $list = new BC_ListTable($paiement, $list, 1, null, $titre);
        
        if ($this->socid) {
            $list->addJoin('paiementfourn_facturefourn', 'a.rowid = paiement_fact.fk_paiementfourn', 'paiement_fact');
            $list->addJoin('facture_fourn', 'paiement_fact.fk_facturefourn = parent.rowid', 'parent');
            $list->addFieldFilterValue('parent.fk_soc', $this->socid);
        }

        
        return $list->renderHtml();
    }
}
