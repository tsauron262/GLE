<?php

require_once DOL_DOCUMENT_ROOT . '/bimpreservation/controllers/reservationController.php';

class entrepotController extends reservationController
{

    public function displayHead()
    {
        
    }

    public function renderHtml()
    {
        if (!BimpTools::isSubmit('id')) {
            return BimpRender::renderAlerts('ID de l\'entrepôt absent');
        }

        $entrepot = $this->config->getObject('', 'entrepot');
        if (is_null($entrepot) || !isset($entrepot->id) || !$entrepot->id) {
            return BimpRender::renderAlerts('Aucun entrepôt trouvé pour l\'ID ' . BimpTools::getValue('id', ''));
        }

        $html = '';

        $html .= '<div class="page_content container-fluid">';
        $html .= '<h1>Liste des réservations pour l\'entrepôt "' . $entrepot->libelle . ($entrepot->description ? ' - ' . $entrepot->description : '') . '"</h1>';

        $html .= BimpRender::renderNavTabs(array(
                    array(
                        'id'      => 'res_reserve',
                        'title'   => 'A réserver',
                        'content' => $this->renderReservationsTab($entrepot, 'Réservations à traiter', 'to_reserve', array(
                            'in' => '2,100'
                        ))
                    ),
                    array(
                        'id'      => 'res_to_process',
                        'title'   => 'A traiter',
                        'content' => $this->renderReservationsTab($entrepot, 'Réservations à traiter', 'to_process', 0)
                    ),
                    array(
                        'id'      => 'res_to_deliver',
                        'title'   => 'A livrer',
                        'content' => $this->renderReservationsTab($entrepot, 'Réservations à livrer', 'to_deliver', array(
                            'and' => array(
                                array(
                                    'operator' => '<',
                                    'value'    => 300
                                ),
                                array(
                                    'operator' => '>=',
                                    'value'    => 200
                                )
                            )
                        ))
                    ),
                    array(
                        'id'      => 'res_completed',
                        'title'   => 'Terminées',
                        'content' => $this->renderReservationsTab($entrepot, 'Réservations terminées', 'completed', array(
                            'operator' => '>=',
                            'value'    => 300
                        ))
                    ),
                    array(
                        'id'      => 'all_res',
                        'title'   => 'Toutes les Réservations',
                        'content' => $this->renderReservationsTab($entrepot, 'Toutes les Réservations', 'all', array())
                    ),
                    array(
                        'id'      => 'supplier_orders',
                        'title'   => 'Gestion des commandes fournisseurs',
                        'content' => $this->renderSupplierOrdersTab($entrepot)
                    )
        ));
        $html .= '</div>';
        return $html;
    }

    protected function renderReservationsTab($entrepot, $title, $suffixe, $status_filter = array())
    {
        $html = '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $resa = BimpObject::getInstance($this->module, 'BR_Reservation');
        $list = new BC_ListTable($resa, 'entrepot', 1, null, $title);
        $list->identifier .= '_' . $suffixe;
        $list->addFieldFilterValue('id_entrepot', (int) $entrepot->id);
//        $list->addFieldFilterValue('type', BR_Reservation::BR_RESERVATION_COMMANDE);  // pas que les enstock aussi les pret et autres
        if (count($status_filter)) {
            $list->addFieldFilterValue('status', $status_filter);
        }
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderSupplierOrdersTab($entrepot)
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-lg-12">';

        $rcf = BimpObject::getInstance($this->module, 'BR_ReservationCmdFourn');
        $list = new BC_ListTable($rcf, 'default', 1, null, 'Liste des réservations en commande / à commander');
        $list->addFieldFilterValue('id_entrepot', (int) $entrepot->id);
        $html .= $list->renderHtml();

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
