<?php

class shipmentsController extends BimpController
{

    public function renderHtml()
    {
        $html = '';

        $expedition = BimpObject::getInstance('bimpreservation', 'BR_CommandeShipment');
        $list = new BC_ListTable($expedition, 'default', 1, null, 'Expéditions', 'sign-out');

        $id_entrepot = (int) BimpTools::getValue('id_entrepot');
        if ($id_entrepot) {
            $list->addFieldFilterValue('a.id_entrepot', $id_entrepot);
        }

        $invoiced = BimpTools::getValue('invoiced', null);

        $joins = array();

        if (!is_null($invoiced)) {
            $list->addJoin('commande', 'commande.rowid = a.id_commande_client', 'commande');

            if ((int) $invoiced > 0) {
                $list->addFieldFilterValue('or_invoiced', array(
                    'or' => array(
                        'a.id_facture'        => array(
                            'operator' => '>',
                            'value'    => 0
                        ),
                        'commande.id_facture' => array(
                            'operator' => '>',
                            'value'    => 0
                        )
                    )
                ));
            } else {
                $list->addFieldFilterValue('a.id_facture', 0);
                $list->addFieldFilterValue('commande.id_facture', 0);
            }
        }

        $shipped = BimpTools::getValue('shipped', null);

        if (!is_null($shipped)) {
            if ((int) $shipped > 0) {
                $list->addFieldFilterValue('a.date_shipped', 'IS_NOT_NULL');
            } else {
                $list->addFieldFilterValue('a.date_shipped', 'IS_NULL');
            }
        }

        $html .= '<div class="page_content container-fluid">';
        $html .= '<h1>Expéditions de commandes client</h1>';

        global $db;

        if ($id_entrepot) {
            BimpTools::loadDolClass('product/stock', 'entrepot');
            $entrepot = new Entrepot($db);
            if ($entrepot->fetch((int) $id_entrepot) > 0) {
                $html .= '<h2>Entrepôt: ' . $entrepot->getNomUrl(1) . '</h2>';
            } else {
                $html .= BimpRender::renderAlerts('L\'entrepôt d\'ID ' . $id_entrepot . ' n\'existe pas');
            }
        }

        if (!is_null($invoiced) || !is_null($shipped)) {
            $html .= '<p>';
            if (!is_null($shipped)) {
                if ((int) $shipped) {
                    $html .= '<span class="success"><i class="fa fa-check iconLeft"></i>Expédiées</span>';
                } else {
                    $html .= '<span class="danger"><i class="fa fa-times iconLeft"></i>Non expédiées</span>';
                }
                if (!is_null($invoiced)) {
                    $html .= '&nbsp;&nbsp;-&nbsp;&nbsp;';
                }
            }
            if (!is_null($invoiced)) {
                if ((int) $invoiced) {
                    $html .= '<span class="success"><i class="fa fa-check iconLeft"></i>Facturées</span>';
                } else {
                    $html .= '<span class="danger"><i class="fa fa-times iconLeft"></i>Non facturées</span>';
                }
            }
            $html .= '</p>';
        }

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
