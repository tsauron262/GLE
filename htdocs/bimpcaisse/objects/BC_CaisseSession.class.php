<?php

class BC_CaisseSession extends BimpObject
{

    public function getPaymentsInfos()
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $infos = array();

        $paiement = BimpObject::getInstance('bimpcaisse', 'BC_Paiement');
        $list = $paiement->getList(array(
            'id_caisse'         => (int) $this->getData('id_caisse'),
            'id_caisse_session' => (int) $this->id
                ), null, null, 'id', 'asc', 'array', array('id'));
        foreach ($list as $item) {
            $paiement = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Paiement', (int) $item['id']);
            if ($paiement->isLoaded()) {
                $obj = $paiement->getChildObject('paiement');
                if (BimpObject::objectLoaded($obj)) {
                    if (!isset($infos[$obj->dol_object->type_code])) {
                        $infos[$obj->dol_object->type_code] = array(
                            'label'  => $obj->dol_object->type_libelle,
                            'number' => 0,
                            'total'  => 0
                        );
                    }

                    $infos[$obj->dol_object->type_code]['number'] ++;
                    $infos[$obj->dol_object->type_code]['total'] += (float) $paiement->getAmount();
                }
            }
        }

        return $infos;
    }

    public function renderPaymentsInfos()
    {
        $html = '';

        $html .= '<div class="objectFieldsTableContainer">';
        $html .= '<table class="objectFieldsTable foldable open">';

        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="3"><i class="fas fa5-chart-bar iconLeft"></i>Informations paiements par type</th>';
        $html .= '</tr>';

        $html .= '<tr class="col_headers">';
        $html .= '<th>Type</th>';
        $html .= '<th>Nombre</th>';
        $html .= '<th>Montant total</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        if (!$this->isLoaded()) {
            $html .= '<tr>';
            $html .= '<td colspan="3">';
            $html .= BimpRender::renderAlerts('ID de la session de caisse absent');
            $html .= '</td>';
            $html .= '</tr>';
        } else {
            $payments_infos = $this->getPaymentsInfos();

            foreach ($payments_infos as $payment_type) {
                $html .= '<tr>';
                $html .= '<th>' . $payment_type['label'] . '</th>';
                $html .= '<td>' . $payment_type['number'] . '</td>';
                $html .= '<td>' . BimpTools::displayMoneyValue((float) $payment_type['total'], 'EUR') . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';

        $html .= '</table>';
        $html .= '</div>';


        return $html;
    }

    public function renderPaymentsHistory($full_list = false)
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la session de caisse absent');
        }

        $caisse = $this->getParentInstance();

        if (!BimpObject::objectLoaded($caisse)) {
            return BimpRender::renderAlerts('ID de la caisse absent');
        }

        $bc_paiement = BimpObject::getInstance('bimpcaisse', 'BC_Paiement');
        $list_name = ($full_list ? 'full_session' : 'session');
        $list = new BC_ListTable($bc_paiement, $list_name, 1, null, 'Liste des paiements', 'fas_hand-holding-usd');
        $list->addFieldFilterValue('id_caisse', (int) $caisse->id);
        $list->addFieldFilterValue('id_caisse_session', (int) $this->id);
        return $list->renderHtml();
    }
    
    public function renderVentesList()
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la session de caisse absent');
        }

        $caisse = $this->getParentInstance();

        if (!BimpObject::objectLoaded($caisse)) {
            return BimpRender::renderAlerts('ID de la caisse absent');
        }

        $bc_vente = BimpObject::getInstance('bimpcaisse', 'BC_Vente');
        $list = new BC_ListTable($bc_vente, 'full', 1, null, 'Liste des vente', 'fas_money-check-alt');
        $list->addFieldFilterValue('id_caisse', (int) $caisse->id);
        $list->addFieldFilterValue('id_caisse_session', (int) $this->id);
        return $list->renderHtml();
    }

    // Overrides: 

    public function getName($with_generic = true)
    {
        $name = '';
        if ($this->isLoaded()) {
            $caisse = $this->getParentInstance();
            $name = 'Caisse';
            if (BimpObject::objectLoaded($caisse)) {
                $name .= ' "' . $caisse->getData('name') . '"';
            } else {
                $name = ' inconnue';
            }

            $from = $this->getData('date_open');
            $to = $this->getData('date_closed');

            if (!is_null($from) && $from) {
                $DT = new DateTime($from);
                $name .= ' - Session ';
                if (!is_null($to) && $to) {
                    $name .= ' du ' . $DT->format('d/m/Y à H:i');
                    $DT = new DateTime($to);
                    $name .= ' au ' . $DT->format('d/m/Y à H:i');
                } else {
                    $name .= 'ouverte depuis le ' . $DT->format('d/m/Y à H:i');
                }
            }
        }

        return $name;
    }
}
