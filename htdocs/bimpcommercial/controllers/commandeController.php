<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/controllers/BimpCommController.php';

class commandeController extends BimpCommController
{

    public function init()
    {
        global $langs;

        $langs->load('orders');
        $langs->load('sendings');
        $langs->load('companies');
        $langs->load('bills');
        $langs->load('propal');
        $langs->load('deliveries');
        $langs->load('sendings');
        $langs->load('products');
        $langs->load('other');
    }
    
//    public function renderLogisticTab()
//    {
//        if (!BimpTools::isSubmit('id')) {
//            return BimpRender::renderAlerts('ID de la commande absent');
//        }
//
//        $commande = $this->config->getObject('', 'commande');
//        if (!BimpObject::objectLoaded($commande)) {
//            return BimpRender::renderAlerts('Aucune commande trouvée pour l\'ID ' . BimpTools::getValue('id', ''));
//        }
//
//        if ($commande->dol_object->statut < 1) {
//            return BimpRender::renderAlerts('Cette commande doit etre validée pour accéder à cet onglet');
//        }
//
//        $_GET['id_entrepot'] = (int) $commande->dol_object->array_options['options_entrepot'];
//
//        $html = '';
//
//        $html .= '<div class="page_content container-fluid">';
//        $html .= '<h1>Commande client "' . $commande->dol_object->ref . '"</h1>';
//
//        $errors = $commande->checkIntegrity();
//
//        if (count($errors)) {
//            $html .= BimpRender::renderAlerts('Des incohérences dans les données de cette commande ont été détectées. Des correctifs sont nécessaires');
//            $html .= BimpRender::renderAlerts($errors);
//            $subject = '[URGENT] Erreurs sur la commande ' . $commande->id;
//            $mail_msg = DOL_URL_ROOT . '/bimpreservation/index.php?fc=commande&id=' . $commande->id . "\n\n";
//            $mail_msg .= 'Erreur(s): ' . "\n";
//            foreach ($errors as $error) {
//                $mail_msg .= ' - ' . $error . "\n";
//            }
//            mailSyn2($subject, 'f.martinez@bimp.fr', 'BIMP<no-reply@bimp.fr>', $mail_msg);
//        }
//
//        $html .= BimpRender::renderNavTabs(array(
//                    array(
//                        'id'      => 'reservations',
//                        'title'   => 'logistique produits',
//                        'content' => $this->renderReservationsTab($commande)
//                    ),
//                    array(
//                        'id'      => 'shipments',
//                        'title'   => 'Expéditions',
//                        'content' => $this->renderShipmentsTab($commande)
//                    ),
//                    array(
//                        'id'      => 'supplier_orders',
//                        'title'   => 'Gestion des commandes fournisseurs',
//                        'content' => $this->renderSupplierOrdersTab($commande)
//                    ),
//                    array(
//                        'id'      => 'products',
//                        'title'   => 'Récapitulatif Produits / Services',
//                        'content' => $this->renderProductsTab($commande)
//                    ),
//                    array(
//                        'id'      => 'avoirs',
//                        'title'   => 'Avoirs',
//                        'content' => $this->renderAvoirsTab($commande)
//                    ),
//        ));
//
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    protected function renderProductsTab(BimpObject $commande)
//    {
//        $html = '';
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        $html .= '<div class="buttonsContainer align-right">';
//
//        if ((int) $commande->getData('id_facture')) {
//            $html .= $this->renderGlobalFactureButton($commande);
//            $html .= '<button type="button" class="btn btn-default disabled bs-popover" onclick="" ';
//            $html .= BimpRender::renderPopoverData('Une facture globale a été créée, il n\'est plus possible d\'ajouter des produits ou des services', 'bottom');
//            $html .= '>';
//            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter un produit / service';
//            $html .= '</button>';
//        } else {
//            $onclick = $commande->getJsActionOnclick('createFacture', array(), array('form_name' => 'facture'));
//            $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
//            $html .= '<i class="fas fa5-file-alt iconLeft"></i>Facturer tous les élements non facturés';
//            $html .= '</button>';
//
//            $onclick = $commande->getJsActionOnclick('addLine', array(), array(
//                'form_name' => 'add_line'
//            ));
//            $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
//            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter un produit / service';
//            $html .= '</button>';
//        }
//
//        $html .= '</div>';
//
//        $orderLine = BimpObject::getInstance($this->module, 'BR_OrderLine');
//        $list = new BC_ListTable($orderLine, 'default', 1, $commande->id, 'Produits et services');
//        $list->addFieldFilterValue('id_commande', (int) $commande->id);
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    protected function renderReservationsTab($commande)
//    {
//        $html = '';
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        $html .= '<div class="buttonsContainer" style="display: inline-block">';
//        $html .= '<button id="openEquipmentsFormButton" type="button" class="btn btn-primary btn-large"';
//        $html .= ' onclick="openEquipmentsForm();">';
//        $html .= '<i class="fa fa-arrow-circle-down iconLeft"></i>Attribuer des équipements';
//        $html .= '</button>';
//        $html .= '</div>';
//
//        $html .= '<div class="buttonsContainer align-right" style="display: inline-block; float: right;">';
//
//        if ((int) $commande->getData('id_facture')) {
//            $html .= $this->renderGlobalFactureButton($commande);
//            $html .= '<button type="button" class="btn btn-default disabled bs-popover" onclick="" ';
//            $html .= BimpRender::renderPopoverData('Une facture globale a été créée, il n\'est plus possible d\'ajouter des produits ou des services', 'bottom');
//            $html .= '>';
//            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter un produit / service';
//            $html .= '</button>';
//        } else {
//            $onclick = $commande->getJsActionOnclick('createFacture', array(), array('form_name' => 'facture'));
//            $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
//            $html .= '<i class="fas fa5-file-alt iconLeft"></i>Facturer tous les élements non facturés';
//            $html .= '</button>';
//
//            $onclick = $commande->getJsActionOnclick('addLine', array(), array(
//                'form_name' => 'add_line'
//            ));
//            $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
//            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter un produit / service';
//            $html .= '</button>';
//        }
//
//        $html .= '</div>';
//
//        $html .= $this->renderEquipmentForm((int) $commande->id);
//
//        $reservation = BimpObject::getInstance($this->module, 'BR_Reservation');
//        $list = new BC_ListTable($reservation, 'commandes', 1, null, 'Réservations et statuts des produits');
//        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    protected function renderShipmentsTab($commande)
//    {
//        $html = '';
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        if ((int) $commande->getData('id_facture')) {
//            $html .= '<div class="buttonsContainer align-right">';
//            $html .= $this->renderGlobalFactureButton($commande);
//            $html .= '</div>';
//        }
//
//        $shipment = BimpObject::getInstance($this->module, 'BL_CommandeShipment');
//        $list = new BC_ListTable($shipment, 'commandes', 1, (int) $commande->id, 'Liste des expéditions', 'sign-out');
////        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    protected function renderSupplierOrdersTab($commande)
//    {
//        $html = '';
//
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        $rcf = BimpObject::getInstance($this->module, 'BR_ReservationCmdFourn');
//        $list = new BC_ListTable($rcf, 'default', 1, null, 'Liste des réservations en commande / à commander');
//        $list->addFieldFilterValue('id_commande_client', (int) $commande->id);
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    protected function renderAvoirsTab($commande)
//    {
//        $html = '';
//
//        $html .= '<div class="row">';
//        $html .= '<div class="col-lg-12">';
//
//        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
//        $list = new BC_ListTable($instance, 'default', 1, null, 'Liste des avoirs en lien avec cette commande');
//        $list->addObjectAssociationFilter($commande, $commande->id, 'avoirs');
//        $list->addObjectChangeReload('BR_ReservationShipment');
//        $list->addObjectChangeReload('BR_ServiceShipment');
//        $list->addObjectChangeReload('BL_CommandeShipment');
//        $list->addObjectChangeReload('BR_Reservation');
//        $list->addObjectChangeReload('BR_OrderLine');
//
//        $html .= $list->renderHtml();
//
//        $html .= '</div>';
//        $html .= '</div>';
//
//        return $html;
//    }
//
//    protected function renderGlobalFactureButton(Bimp_Commande $commande)
//    {
//        $html = '';
//        $facture = $commande->getChildObject('facture');
//        if (BimpObject::objectLoaded($facture)) {
//            $ref = $facture->getData('facnumber');
//            $label = '';
//            $shipment = BimpObject::getInstance('bimpreservation', 'BL_CommandeShipment');
//            if (count($shipment->getList(array(
//                                'id_commande_client' => (int) $commande->id,
//                                'id_facture'         => array(
//                                    'operator' => '>',
//                                    'value'    => 0
//                                )
//                    )))) {
//                $label = 'Facture des éléments facturés hors expédition';
//            } else {
//                $label = 'Facture globale';
//            }
//
//            $html .= '<strong>' . $label . ': </strong>';
//
//            $html .= BimpObject::getInstanceNomUrlWithIcons($facture);
//
//            if ((int) $facture->getData('fk_statut') > 0) {
//                if (file_exists(DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf')) {
//                    $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities($ref . '/' . $ref . '.pdf');
//                    $onclick = 'window.open(\'' . htmlentities($url) . '\')';
//                    $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
//                    $html .= '<i class="' . BimpRender::renderIconClass('fas_file-pdf') . ' iconLeft"></i>PDF Facture';
//                    $html .= '</button>';
//                }
//            } else {
//                $onclick = $commande->getJsActionOnclick('validateFacture', array(), array(
//                    'confirm_msg' => 'La facture ne sera plus supprimable. Veuillez confirmer'
//                ));
//                $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
//                $html .= '<i class="fa fa-check iconLeft"></i>Valider la facture';
//                $html .= '</button>';
//            }
//        } else {
//            $html .= BimpRender::renderAlerts('Erreur: ID de la facture hors expédition invalide');
//        }
//
//        return $html;
//    }
//
//    protected function ajaxProcessCreateShipment()
//    {
//        $success = 'Création de l\'expédition effectuée avec succès';
//
//        BimpObject::loadClass($this->module, 'BR_Reservation');
//        $errors = BR_Reservation::createShipment((int) BimpTools::getValue('id_commande_client', 0));
//
//        die(json_encode(array(
//            'errors'     => $errors,
//            'success'    => $success,
//            'request_id' => BimpTools::getValue('request_id', 0)
//        )));
//    }
}
