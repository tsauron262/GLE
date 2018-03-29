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
}
