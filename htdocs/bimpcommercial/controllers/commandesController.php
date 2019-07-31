<?php

class commandesController extends BimpController
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

            dol_banner_tab($soc, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=commandes');
        }
    }

    public function renderCommandesTab()
    {
//        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeLine');
//        Bimp_CommandeLine::checkAllQties();
        
        $list = 'default';
        $titre = 'Commandes';
        if ($this->socid) {
            $societe = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $this->socid);

            if (!BimpObject::objectLoaded($societe)) {
                return BimpRender::renderAlerts('ID du client invalide');
            }
            $list = 'client';
            $titre .= ' du client "' . $societe->getData('code_client') . ' - ' . $societe->getData('nom');
        }

        $propal = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');

        $list = new BC_ListTable($propal, $list, 1, null, $titre);

        if ($this->socid) {
            $list->addFieldFilterValue('fk_soc', (int) $societe->id);
            $list->params['add_form_values']['fields']['fk_soc'] = (int) $societe->id;
        }

        return $list->renderHtml();
    }

    public function renderShipmentsTab()
    {
        $shipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
        $list = new BC_ListTable($shipment, 'default', 1, null, 'Liste des expéditions', 'fas_shipping-fast');
        return $list->renderHtml();
    }

    public function renderProdsTabs()
    {
//        $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');

        $bc_list = new BC_ListTable($line, 'general', 1, null, 'Liste des produits en commande', 'fas_bars');
        $bc_list->addJoin('commande', 'a.id_obj = parent.rowid', 'parent');
        $bc_list->addFieldFilterValue('parent.fk_statut', array(
            'operator' => '>',
            'value'    => 0
        ));
        
//        if ($id_entrepot) {
//            $bc_list->addJoin('commande_extrafields', 'a.id_obj = cef.fk_object', 'cef');
//            $bc_list->addFieldFilterValue('cef.entrepot', $id_entrepot);
//        }

        return $bc_list->renderHtml();
    }
}
