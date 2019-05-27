<?php

class commandeController extends BimpController
{
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