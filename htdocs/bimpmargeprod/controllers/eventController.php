<?php

class eventController extends BimpController
{

    public function ajaxProcessLoadEventMontantDetails()
    {
        $errors = array();
        $id_event_montant = BimpTools::getValue('id_event_montant');
        $details = array();
        
        $eventMontant = null;

        if (is_null($id_event_montant)) {
            $errors[] = 'ID du montant absent';
        } else {
            $eventMontant = BimpCache::getBimpObjectInstance('bimpmargeprod', 'BMP_EventMontant', (int) $id_event_montant);
            if (!BimpObject::objectLoaded($eventMontant)) {
                $errors[] = 'Le montant d\'ID ' . $id_event_montant . ' n\'existe pas';
            } else {
                $eventMontantDetail = BimpObject::getInstance('bimpmargeprod', 'BMP_EventMontantDetail');
                $details = $eventMontantDetail->getList(array(
                    'id_event_montant' => (int) $id_event_montant
                ));
            }
        }

        $html = '';

        if (count($details)) {
            $tva_tx = (float) $eventMontant->getTvaTx();
            $total_ht = 0;
            $total_ttc = 0;
            $total_qty = 0;
            
            $html .= '<table class="objectSubList">';
            $html .= '<thead>';
            $html .= '<th width="40%">Libellé</th>';
            $html .= '<th>Quantité</th>';
            $html .= '<th>Prix unitaire HT</th>';
            $html .= '<th>Total HT</th>';
            $html .= '<th>Total TTC</th>';
            $html .= '</thead>';

            $html .= '<tbody>';
            foreach ($details as $detail) {
                $total_line_ht = (int) $detail['quantity'] * (float) $detail['unit_price'];
                $total_line_ttc = BimpTools::calculatePriceTaxIn($total_line_ht, $tva_tx);
                $total_ht += $total_line_ht;
                $total_ttc += $total_line_ttc;
                $total_qty += (int) $detail['quantity'];
                
                $html .= '<tr>';
                $html .= '<td>' . $detail['label'] . '</td>';
                $html .= '<td>' . $detail['quantity'] . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue((float) $detail['unit_price'], 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_line_ht, 'EUR') . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_line_ttc, 'EUR') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';

            $html .= '<tfoot>';
            $html .= '<tr>';
            $html .= '<th>Total :</th>';
            $html .= '<td>'.$total_qty.'</td>';
            $html .= '<td></td>';
            $html .= '<td>'.BimpTools::displayMoneyValue($total_ht, 'EUR').'</td>';
            $html .= '<td>'.BimpTools::displayMoneyValue($total_ttc, 'EUR').'</td>';
            $html .= '</tr>';
            $html .= '</tfoot>';

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
