<?php

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

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

//    public function renderHtml() {
//        global $db;
//        $html = '';
//        $id_soc = (int) GETPOST('id');
////        if (isset($id_soc) and $id_soc > 0)
////            return '';
//        return $this->renderObjects();
//    }

    public function renderObjects() {
        global $db;
        $html = '';
        $id_soc = (int) GETPOST('id');
        $is_submit_id = BimpTools::isSubmit('id');
        if ($is_submit_id) {
            $societe = new Societe($db);
            $societe->fetch($id_soc);
        }

        if ($societe->client > 0 or ! $is_submit_id) {
            // Propal
            $propale = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal');
            $list_propale = new BC_ListTable($propale, $is_submit_id ? 'client' : 'default', 1, null, "Propositions commerciales");
            if ($is_submit_id)
                $list_propale->addFieldFilterValue('fk_soc', $id_soc);
            $html .= $list_propale->renderHtml();

            // Facture
            $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
            $list_facture = new BC_ListTable($facture, $is_submit_id ? 'client' : 'default', 1, null, "Factures");
            if ($is_submit_id)
                $list_facture->addFieldFilterValue('fk_soc', $id_soc);
            $html .= $list_facture->renderHtml();

            // Commande client
            $commande_client = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
            $list_commande_client = new BC_ListTable($commande_client, $is_submit_id ? 'client' : 'default', 1, null, "Commandes client");
            if ($is_submit_id)
                $list_commande_client->addFieldFilterValue('fk_soc', $id_soc);
            $html .= $list_commande_client->renderHtml();

            // Bon livraison
            $shipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            $list_shipment = new BC_ListTable($shipment, $is_submit_id ? 'default' : 'default', 1, null, "Livraison client");
            if ($is_submit_id) {
                $list_shipment->addFieldFilterValue('cf.fk_soc', $id_soc);
                $list_shipment->addJoin('commande', 'a.id_commande_client=cf.rowid', 'cf');
            }
            $html .= $list_shipment->renderHtml();
        }

        if ($societe->fournisseur > 0 or ! $is_submit_id) {
            // Commande fournisseur
            $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');
            $list_commande = new BC_ListTable($commande_fourn, $is_submit_id ? 'fourn' : 'default', 1, null, "Commandes fournisseur");
            if ($is_submit_id)
                $list_commande->addFieldFilterValue('fk_soc', $id_soc);
            $html .= $list_commande->renderHtml();

            // Bon réception 
            $reception = BimpObject::getInstance('bimplogistique', 'BL_CommandeFournReception');
            $list_reception = new BC_ListTable($reception, $is_submit_id ? 'default' : 'default', 1, null, "Réceptions fournisseur");
            if ($is_submit_id) {
                $list_reception->addFieldFilterValue('cf.fk_soc', $id_soc);
                $list_reception->addJoin('commande_fournisseur', 'a.id_commande_fourn = cf.rowid', 'cf');
            }
            $html .= $list_reception->renderHtml();
            
            
            // factures fournisseur
            $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn');
            $list_commande = new BC_ListTable($commande_fourn, $is_submit_id ? 'fourn' : 'default', 1, null, "Facture fournisseur");
            if ($is_submit_id)
                $list_commande->addFieldFilterValue('fk_soc', $id_soc);
            $html .= $list_commande->renderHtml();
        }

        return $html;
    }
    
    public function getIdSoc(){
        if (BimpTools::isSubmit('id'))
            return (int) GETPOST('id');
        return 0;
    }

    public function renderObjectsLines() {
        if(!$this->getIdSoc())
            return '';
        
        
        global $db;
        $html = '';
        $id_soc = (int) GETPOST('id');
        $is_submit_id = BimpTools::isSubmit('id');
        if ($is_submit_id) {
            $societe = new Societe($db);
            $societe->fetch($id_soc);
        }

        if ($societe->client > 0 or ! $is_submit_id) {
            // Propal
            $propale = BimpObject::getInstance('bimpcommercial', 'Bimp_PropalLine');
            $list_propale = new BC_ListTable($propale, $is_submit_id ? 'global' : 'global', 1, null, "Lignes de propositions commerciales");
            if ($is_submit_id) {
                $list_propale->addFieldFilterValue('p.fk_soc', $id_soc);
                $list_propale->addJoin('propal', 'a.id_obj=p.rowid', 'p');
            }
            $html .= $list_propale->renderHtml();

            // Commande client
            $commande_client = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeLine');
            $list_commande_client = new BC_ListTable($commande_client, $is_submit_id ? 'global' : 'global', 1, null, "Lignes de commandes client");
            if ($is_submit_id) {
                $list_commande_client->addFieldFilterValue('c.fk_soc', $id_soc);
                $list_commande_client->addJoin('commande', 'a.id_obj=c.rowid', 'c');
            }
            $html .= $list_commande_client->renderHtml();

            // Facture
            $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
            $list_facture = new BC_ListTable($facture, $is_submit_id ? 'global' : 'global', 1, null, "Lignes de factures");
            if ($is_submit_id) {
                $list_facture->addFieldFilterValue('f.fk_soc', $id_soc);
                $list_facture->addJoin('facture', 'a.id_obj=f.rowid', 'f');
            }
            $html .= $list_facture->renderHtml();
        }

        if ($societe->fournisseur > 0 or ! $is_submit_id) {
            // Commande fournisseur
            $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
            $list_commande = new BC_ListTable($commande_fourn, $is_submit_id ? 'global' : 'global', 1, null, "Lignes de commandes fournisseur");
            if ($is_submit_id) {
                $list_commande->addFieldFilterValue('cf.fk_soc', $id_soc);
                $list_commande->addJoin('commande_fournisseur', 'a.id_obj=cf.rowid', 'cf');
            }
            $html .= $list_commande->renderHtml();
            
            // Commande fournisseur
            $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFournLine');
            $list_commande = new BC_ListTable($commande_fourn, $is_submit_id ? 'global' : 'global', 1, null, "Lignes de factures fournisseur");
            if ($is_submit_id) {
                $list_commande->addFieldFilterValue('ff.fk_soc', $id_soc);
                $list_commande->addJoin('facture_fourn', 'a.id_obj=ff.rowid', 'ff');
            }
            $html .= $list_commande->renderHtml();
        }

        return $html;
    }
    
    function renderAccueil(){
        if($this->getIdSoc())
            return $this->renderObjects();
        else
            return $this->renderMenu ();
    }
    
    function renderMenu(){
        $html = '';
        $html .= '<a href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=propals" class="btn btn-default">Devis</a>';
        $html .= '<br/><br/>';
        $html .= '<a href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=commandes" class="btn btn-default">Commandes</a>';
        $html .= '<br/><br/>';
        $html .= '<a href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=factures" class="btn btn-default">Factures</a>';
        $html .= '<br/><br/>';
        $html .= '<a href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=commandesFourn" class="btn btn-default">Commandes Fournisseurs</a>';
        $html .= '<br/><br/>';
        $html .= '<a href="'.DOL_URL_ROOT.'/bimpcommercial/index.php?fc=facturesFourn" class="btn btn-default">Factures Fournisseurs</a>';
        $html .= '<br/><br/>';
        
        
        return $html;
    }
}
