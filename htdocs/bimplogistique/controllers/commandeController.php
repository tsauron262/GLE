<?php

require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class commandeController extends BimpController
{

//    public function displayHead()
//    {
//        global $langs;
//        $commande = $this->config->getObject('', 'commande');
//        require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
//        $head = commande_prepare_head($commande->dol_object);
//        dol_fiche_head($head, 'bimplogisitquecommande', $langs->trans("CustomerOrder"), -1, 'order');
//    }

    public function renderContentHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de la commande absent');
        }

        $commande = $this->config->getObject('', 'commande');
        if (!BimpObject::objectLoaded($commande)) {
            return BimpRender::renderAlerts('Aucune commande trouvée pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        if ($commande->getData('fk_statut') < 1) {
            return BimpRender::renderAlerts('Cette commande doit etre validée pour accéder à cet onglet');
        }

        $_GET['id_entrepot'] = (int) $commande->getData('entrepot');

        $html = '';

//        $html .= '<div class="page_content container-fluid">';
//        $html .= '<h1>Commande client "' . $commande->dol_object->ref . '"</h1>';
//        $errors = $commande->checkIntegrity();

        if (count($errors)) {
            $html .= BimpRender::renderAlerts('Des incohérences dans les données de cette commande ont été détectées. Des correctifs sont nécessaires');
            $html .= BimpRender::renderAlerts($errors);
            $subject = '[URGENT] Erreurs sur la commande ' . $commande->id;
            $mail_msg = DOL_URL_ROOT . '/bimpreservation/index.php?fc=commande&id=' . $commande->id . "\n\n";
            $mail_msg .= 'Erreur(s): ' . "\n";
            foreach ($errors as $error) {
                $mail_msg .= ' - ' . $error . "\n";
            }
            mailSyn2($subject, 'f.martinez@bimp.fr', 'BIMP<admin@bimp.fr>', $mail_msg);
        }

        $html .= BimpRender::renderNavTabs(array(
                    array(
                        'id'      => 'reservations',
                        'title'   => 'Logistique produits / services',
                        'content' => $this->renderCommandesLinesLogisticTab($commande)
                    ),
                    array(
                        'id'      => 'shipments',
                        'title'   => 'Expéditions',
                        'content' => $this->renderShipmentsTab($commande)
                    ),
                    array(
                        'id'      => 'supplier_orders',
                        'title'   => 'Commandes fournisseurs',
                        'content' => $this->renderSupplierOrdersTab($commande)
                    ),
                    array(
                        'id'      => 'invoices',
                        'title'   => 'Factures / Avoirs',
                        'content' => $this->renderFacturesTab($commande)
                    ),
        ));

//        $html .= '</div>';

        return $html;
    }

    protected function renderCommandesLinesLogisticTab(Bimp_Commande $commande)
    {
        $html = '';

        if (BimpObject::objectLoaded($commande)) {
            $html .= '<div class="buttonsContainer align-right">';
            $html .= $commande->renderLogistiqueButtons();
            $html .= '</div>';

            $html .= $commande->renderChildrenList('lines', 'logistique', 1);
        } else {
            $html .= BimpRender::renderAlerts('ID de la commande absent');
        }

        return $html;
    }

    protected function renderShipmentsTab($commande)
    {
        $html = '';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        if ((int) $commande->getData('id_facture')) {
            $html .= '<div class="buttonsContainer align-right">';
            $html .= $this->renderGlobalFactureButton($commande);
            $html .= '</div>';
        }

        $shipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
        $list = new BC_ListTable($shipment, 'commandes', 1, (int) $commande->id, 'Liste des expéditions', 'fas_shipping-fast');
        $list->setAddFormValues(array(
            'fields' => array(
                'id_entrepot' => (int) $commande->getData('entrepot')
            )
        ));
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderSupplierOrdersTab(Bimp_Commande $commande)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= $commande->renderView('commandes_fourn', true);

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderFacturesTab($commande)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
        $list = new BC_ListTable($instance, 'default', 1, null, 'Factures');
        $list->params['add_btn'] = 0;
        $list->addObjectAssociationFilter($commande, $commande->id, 'factures');
        $list->addObjectChangeReload('Bimp_Commande');

        $html .= $list->renderHtml();

        $list = new BC_ListTable($instance, 'default', 1, null, 'Avoirs');
        $list->params['add_btn'] = 0;
        $list->addObjectAssociationFilter($commande, $commande->id, 'avoirs');
        $list->addObjectChangeReload('Bimp_Commande');

        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderGlobalFactureButton(Bimp_Commande $commande)
    {
        $html = '';
        $facture = $commande->getChildObject('facture');
        if (BimpObject::objectLoaded($facture)) {
            $ref = $facture->getData('facnumber');
            $label = '';
            $shipment = BimpObject::getInstance('bimplogistique', 'BL_CommandeShipment');
            if (count($shipment->getList(array(
                                'id_commande_client' => (int) $commande->id,
                                'id_facture'         => array(
                                    'operator' => '>',
                                    'value'    => 0
                                )
                    )))) {
                $label = 'Facture des éléments facturés hors expédition';
            } else {
                $label = 'Facture globale';
            }

            $html .= '<strong>' . $label . ': </strong>';

            $html .= BimpObject::getInstanceNomUrlWithIcons($facture);

            if ((int) $facture->getData('fk_statut') > 0) {
                if (file_exists(DOL_DATA_ROOT . '/facture/' . $ref . '/' . $ref . '.pdf')) {
                    $url = DOL_URL_ROOT . '/document.php?modulepart=facture&file=' . htmlentities($ref . '/' . $ref . '.pdf');
                    $onclick = 'window.open(\'' . htmlentities($url) . '\')';
                    $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= '<i class="' . BimpRender::renderIconClass('fas_file-pdf') . ' iconLeft"></i>PDF Facture';
                    $html .= '</button>';
                }
            } else {
                $onclick = $commande->getJsActionOnclick('validateFacture', array(), array(
                    'confirm_msg' => 'La facture ne sera plus supprimable. Veuillez confirmer'
                ));
                $html .= '<button type="button" class="btn btn-default" onclick="' . $onclick . '">';
                $html .= '<i class="fa fa-check iconLeft"></i>Valider la facture';
                $html .= '</button>';
            }
        } elseif ((int) $commande->getData('id_facture')) {
            $html .= '<div style="display: inline-block;">' . $commande->renderChildUnfoundMsg('id_facture', $facture, true, true) . '</div>';
        }

        return $html;
    }

    protected function ajaxProcessCreateShipment()
    {
        $success = 'Création de l\'expédition effectuée avec succès';

        BimpObject::loadClass('bimpreservation', 'BR_Reservation');
        $errors = BR_Reservation::createShipment((int) BimpTools::getValue('id_commande_client', 0));

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}
