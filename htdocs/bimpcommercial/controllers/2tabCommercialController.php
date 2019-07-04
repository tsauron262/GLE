<?php

class tabCommercialController extends BimpController {

    public function displayHead() {
        global $db, $langs, $user;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        $soc = new Societe($db);
        $soc->fetch($_REQUEST['id']);
        $head = societe_prepare_head($soc);
        dol_fiche_head($head, 'commercial', 'Commercial');


        $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        dol_banner_tab($soc, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=commercial');
//        global $langs;
//        $commande = $this->config->getObject('', 'commande');
//        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
//        $head = commande_prepare_head($commande->dol_object);
//        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
        echo 'test display HEAD';
        return 'test display HEAD';
    }

    public function display() {
        echo 'test display';
        // dans les render

        $this->renderPropales();
        $this->renderFactures();
        $this->renderCommandes();
    }

    public function renderPropales() {
        $id_soc = (int) GETPOST('id');
        $propale = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
        $list = new BC_ListTable($propale, 'default', 1, null, "Propositions commerciales");
        $list->addFieldFilterValue('fk_soc', $id_soc);
        $html .= $list->renderHtml();
        echo $html;
        return $html;
    }

    public function renderFactures() {
        $id_soc = (int) GETPOST('id');
        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
        $list = new BC_ListTable($facture, 'default', 1, null, "Factures");
        $list->addFieldFilterValue('fk_soc', $id_soc);
        $html .= $list->renderHtml();
        echo $html;
        return $html;
    }

    public function renderCommandes() {
        $id_soc = (int) GETPOST('id');
        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
//        echo '<pre>';
//        print_r($commande);
//        die();
        $list = new BC_ListTable($commande, 'default', 1, null, "Commandes");
        $list->addFieldFilterValue('fk_soc', $id_soc);
        $html .= $list->renderHtml();
        echo $html;
        return $html;
    }

    public function renderLivraison() {
        $id_soc = (int) GETPOST('id');
        $livraison = BimpObject::getInstance('bimpcommercial', 'BL_CommandeShipment');
//        echo '<pre>';
//        print_r($commande);
//        die();
        $list = new BC_ListTable($livraison, 'default', 1, null, "Commandes");
//        $list->addFieldFilterValue('fk_soc', $id_soc);
        $html .= $list->renderHtml();
        echo $html;
        return $html;
    }

}
