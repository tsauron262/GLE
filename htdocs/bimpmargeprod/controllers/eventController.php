<?php

class eventController extends BimpController
{

    public function ajaxProcessLoadEventMontantDetails()
    {
        $errors = array();
        $id_event_montant = BimpTools::getValue('id_event_montant');
        $details = array();

        if (is_null($id_event_montant)) {
            $errors[] = 'ID du montant absent';
        } else {
            $eventMontantDetail = BimpObject::getInstance('bimpmargeprod', 'BMP_EventMontantDetail');
            $details = $eventMontantDetail->getList(array(
                'id_event_montant' => (int) $id_event_montant
            ));
        }

        $html = '';

        if (count($details)) {
            $html .= '<table class="objectSubList">';
            $html .= '<thead>';
            $html .= '<th width="40%">Libellé</th>';
            $html .= '<th>Quantité</th>';
            $html .= '<th>Prix unitaire HT</th>';
            $html .= '<th>Total HT</th>';
            $html .= '</thead>';

            $html .= '<tbody>';
            foreach ($details as $detail) {
                $html .= '<tr>';
                $html .= '<td>' . $detail['label'] . '</td>';
                $html .= '<td>' . $detail['quantity'] . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue((float) $detail['unit_price'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue((int) $detail['quantity'] * (float) $detail['unit_price'], 'EUR') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        } else {
            $html = BimpRender::renderAlerts('Aucun détail enregistré pour ce montant', 'warning');
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}
