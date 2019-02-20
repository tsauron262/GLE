function addSelectedCommandeLinesToShipment($button, list_id, id_commande) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var $selected = $list.find('tbody').find('input.item_check:checked')

    if (!$selected.length) {
        bimp_msg('Aucune ligne sélectionnée', 'danger');
        return;
    }

    var extra_data = {
        shipment_lines_list: []
    };

    $selected.each(function () {
        extra_data.shipment_lines_list.push(parseInt($(this).data('id_object')));
    });

    setObjectAction($button, {
        module: 'bimpcommercial',
        object_name: 'Bimp_Commande',
        id_object: id_commande
    }, 'linesShipmentQties', extra_data, 'shipment', null, null, null, function ($form, extra_data) {
        return onShipmentFormSubmit($form, extra_data);
    });
}

function addSelectedCommandeLinesToInvoice($button, list_id) {

}

function onShipmentFormSubmit($form, extra_data) {
    var lines = [];

    var $inputs = $form.find('.shipment_lines_inputContainer').find('input.line_shipment_qty');

    $inputs.each(function () {
        var id_line = parseInt($(this).data('id_line'));
        var qty = parseFloat($(this).val());
        lines.push({
            id_line: id_line,
            qty: qty
        });
    });

    extra_data['lines'] = lines;
    return extra_data;
}

function saveCommandeLineShipments($button, id_line) {
    var $form = $('#commande_line_' + id_line + '_shipments_form');

    if ($form.length) {
        var $rows = $form.find('tr.shipment_row');

        var shipments = [];
        if ($rows.length) {
            $rows.each(function () {
                var $row = $(this);
                var data = {};
                data.id_shipment = parseInt($row.data('id_shipment'));
                data.qty = parseFloat($row.find('input.line_shipment_qty').val());
                if (isNaN(data.qty)) {
                    bimp_msg('Quantités invalides pour l\'expédition n°'.$row.data('num_livraison<br/>Veuillez corriger', 'danger'));
                    return;
                }
                var $groupInput = $row.find('input.line_shipment_group');
                if ($groupInput.length) {
                    data.group = parseInt($groupInput.val());
                }
                shipments.push(data);
            });

            var $resultContainer = $form.find('div.ajaxResultContainer');

            setObjectAction($button, {
                module: 'bimpcommercial',
                object_name: 'Bimp_CommandeLine',
                id_object: id_line
            }, 'saveShipments', {
                shipments: shipments
            }, null, $resultContainer, function () {
                var $modalContent = $form.findParentByClass('modal_content');
                if ($.isOk($modalContent)) {
                    var modal_idx = parseInt($modalContent.data('idx'));
                    bimpModal.removeContent(modal_idx);
                }
            });
        }
    }
}

function onCommandeLineShipmentsViewLoaded($view) {
    if (!parseInt($view.data('line_shipments_view_events_init'))) {
        var $modalContent = $view.findParentByClass('modal_content');

        if ($.isOk($modalContent)) {
            var modal_idx = parseInt($modalContent.data('idx'));
            bimpModal.addButton('<i class="fas fa5-save iconLeft"></i>Enregistrer', 'saveCommandeLineShipments($(this), ' + $view.data('id_object') + ')', 'primary', '', modal_idx);
        } else {
            $view.find('.commande_shipments_form').find('.buttonsContainer').show();
        }

        $view.data('line_shipments_view_events_init', 1);
    }
}

function setSelectedCommandeLinesReservationsStatus($button, id_commande, new_status) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $listContainer = $button.findParentByClass('Bimp_CommandeLine_list_table_container');

    if ($.isOk($listContainer)) {
        var $rows = $listContainer.find('.Bimp_CommandeLine_list_table').find('tbody.listRows').find('tr.Bimp_CommandeLine_row');
        var reservations = [];
        $rows.each(function () {
            $(this).find('tr.Bimp_CommandeLine_reservation_row').each(function () {
                var $input = $(this).find('input.reservation_check');
                if ($input.prop('checked')) {
                    reservations.push(parseInt($input.data('id_reservation')));
                }
            });
        });

        if (!reservations.length) {
            bimp_msg('Aucun statut sélectionné', 'danger');
            return;
        }

        var msg = 'Veuillez confirmer ';
        if (new_status === 0) {
            msg += 'la réinitialisation';
        } else if (new_status === 2) {
            msg += 'la mise au statut "A réserver"';
        } else if (new_status === 200) {
            msg += 'réservation';
        } else {
            msg += 'le nouveau statut';
        }
        msg += ' de tous les éléments sélectionnés';

        if (!confirm(msg)) {
            return;
        }

        setObjectAction($button, {
            module: 'bimpcommercial',
            object_name: 'Bimp_Commande',
            id_object: id_commande
        }, 'setLinesReservationsStatus', {
            reservations: reservations,
            status: new_status
        });

        return;
    }

    bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger');
}

$(document).ready(function () {
    $('body').on('viewLoaded', function (e) {
        if (e.$view.hasClass('Bimp_CommandeLine_view_shipments')) {
            onCommandeLineShipmentsViewLoaded(e.$view);
        }
    });
});