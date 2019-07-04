<?php

class tabCommercialController extends BimpController {

    public function displayHead() {
        if (BimpTools::isSubmit('id')) {
            global $db, $langs, $user;
            require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
            $soc = new Societe($db);
            $soc->fetch((int) GETPOST('id'));
            $head = societe_prepare_head($soc);
            dol_fiche_head($head, 'commercial', 'Commercial');
            $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
            dol_banner_tab($soc, 'id', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&fc=client_sav');
        }
    }

    public function renderHtml() {
        $html = '';
        $id_soc = (int) GETPOST('id');
        $propale = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
        if (BimpTools::isSubmit('id')) {
            $list = new BC_ListTable($propale, 'client', 1, null, "Propositions commerciales");
            $list->addFieldFilterValue('fk_soc', $id_soc);
        } else {
            $list = new BC_ListTable($propale, 'default', 1, null, "Propositions commerciales");
        }

        $html .= $list->renderHtml();

        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
        if (BimpTools::isSubmit('id')) {
            $list = new BC_ListTable($facture, 'client', 1, null, "Factures");
            $list->addFieldFilterValue('fk_soc', $id_soc);
        } else {
            $list = new BC_ListTable($facture, 'default', 1, null, "Factures");
        }
        $html .= $list->renderHtml();

        if (BimpTools::isSubmit('id')) {
            $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
            $list = new BC_ListTable($commande, 'client', 1, null, "Commandes");
        } else {
            $list = new BC_ListTable($commande, 'default', 1, null, "Commandes");
        }

        if (BimpTools::isSubmit('id'))
            $list->addFieldFilterValue('fk_soc', $id_soc);
        $html .= $list->renderHtml();

        return $html;
    }

}
