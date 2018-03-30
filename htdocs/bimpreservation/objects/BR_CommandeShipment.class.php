<?php

class BR_CommandeShipment extends BimpObject
{

    public function displayBLButton()
    {
        $url = DOL_URL_ROOT . '/bimpreservation/bl.php?id_commande=' . $this->getData('id_commande_client') . '&num_bl=' . $this->getData('num_livraison');
        $onclick = 'window.open(\'' . $url . '\')';
        $html = '<button type="button" class="btn btn-default" onclick="' . htmlentities($onclick) . '">';
        $html .= '<i class="' . BimpRender::renderIconClass('fas_file-pdf') . ' iconLeft"></i>';
        $html .= 'Bon de livraison';
        $html .= '</button>';

        return $html;
    }

    public function getContactsArray()
    {
        return array(
            0 => 'Addresse de livraison de la commande'
        );
    }

    public function renderProductsQtiesInputs()
    {
        $id_commande = (int) $this->getData('id_commande_client');
        if (!$id_commande) {
            return '';
        }
        
       
        return BimpRender::renderAlerts('Commande invalide');
    }

    public function renderServicesQtiesInputs()
    {
//        $id_commande = (int) $this->getData('id_commande_client');
//        if (!$id_commande) {
            return '';
//        }
//
//        $commande = $this->getChildObject('commande_client');
//        if (!is_null($commande) && isset($commande->id) && $commande->id) {
////            $lines = $commande->lines;
////            return '<pre>' . print_r($lines, 1) . '</pre>';
//        }
//
//        return BimpRender::renderAlerts('Commande invalide');
    }
}
