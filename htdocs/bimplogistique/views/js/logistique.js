// Logistique commande client: 

function setSelectedCommandeLinesReservationsStatus($button, id_commande, new_status) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#Bimp_CommandeLine_logistique_list_table_Bimp_Commande_' + id_commande);

    if ($.isOk($list)) {
        var $rows = $list.find('tbody.listRows').find('tr.Bimp_CommandeLine_row');
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

function onCommandeLinesLogistiqueListLoaded($list) {
    if ($.isOk($list)) {
        $list.find('select.equipment_returned_id_entrepot').each(function () {
            if (!parseInt($(this).data('logistique_events_init'))) {
                $(this).change(function () {
                    saveReturnedEquipmentIdEntrepot($(this));
                });
                $(this).data('logistique_events_init', 1);
            }
        });
    }
}

function saveReturnedEquipmentIdEntrepot($select) {
    if (!$.isOk($select)) {
        bimp_msg('Une erreur est survenue. Entrepôt non enregistré', 'danger');
        return;
    }

    var id_entrepot = parseInt($select.val());

    if (!id_entrepot) {
        bimp_msg('Veuillez sélectionner un entrepôt', 'warning');
        return;
    }

    var id_line = parseInt($select.attr('name').replace(/^line_(\d+)_equipment_(\d+)_id_entrepot$/, '$1'));
    var id_equipment = parseInt($select.attr('name').replace(/^line_(\d+)_equipment_(\d+)_id_entrepot$/, '$2'));

    setObjectAction(null, {
        module: 'bimpcommercial',
        object_name: 'Bimp_CommandeLine',
        id_object: id_line
    }, 'saveReturnedEquipmentEntrepot', {
        id_equipment: id_equipment,
        id_entrepot: id_entrepot
    });
}

// Expéditions commande client: 

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

function onShipmentFormSubmit($form, extra_data) {
    var lines = [];

    var $inputs = $form.find('.shipment_lines_inputContainer').find('input.line_shipment_qty');

    $inputs.each(function () {
        var id_line = parseInt($(this).data('id_line'));
        var qty = parseFloat($(this).val());
        var data = {
            id_line: id_line,
            qty: qty
        };

        var $equipments = $form.find('#shipment_line_' + id_line + '_equipments');
        if ($equipments.length) {
            var equipments = [];
            $equipments.find('.check_list_item_input:checked').each(function () {
                equipments.push(parseInt($(this).val()));
            });
            data.equipments = equipments;
        }


        var $input = $form.find('[name="line_' + id_line + '_group_articles"]');
        if ($input.length) {
            data.group_articles = parseInt($input.val());
        }

        lines.push(data);
    });

    extra_data['lines'] = lines;
    return extra_data;
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
                    bimp_msg('Quantités invalides pour l\'expédition n°' + $row.data('num_livraison') + '<br/>Veuillez corriger', 'danger');
                    return;
                }
                var $input = $row.find('input.line_shipment_group');
                if ($input.length) {
                    data.group = parseInt($input.val());
                }

                var $input = $row.find('select.line_shipment_entrepot');
                if ($input.length) {
                    data.id_entrepot = parseInt($input.val());
                }

                // todo: intégrer liste équipements

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

function setSelectedCommandeLinesReservationsEquipmentsToShipment($button, id_commande) {

    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#Bimp_CommandeLine_logistique_list_table_Bimp_Commande_' + id_commande);

    if (!$.isOk($list)) {
        bimp_msg('Une erreur est survenue (liste non trouvée)', 'danger');
        return;
    }

    var reservations = [];

    $list.find('input.reservation_check:checked').each(function () {
        var id_reservation = parseInt($(this).data('id_reservation'));
        if (id_reservation) {
            reservations.push(id_reservation);
        }
    });

    if (!reservations.length) {
        bimp_msg('Aucun statut sélectionné', 'danger');
        return;
    }

    setObjectAction($button, {
        module: 'bimpcommercial',
        object_name: 'Bimp_Commande',
        id_object: id_commande
    }, 'addEquipmentsToShipment', {
        'reservations': reservations
    }, 'shipment_equipments', null, null, null, function ($form, extra_data) {
        return onShipmentEquipmentsFormSubmit($form, extra_data);
    });
}

function onShipmentLinesViewLoaded($view) {
    if (!parseInt($view.data('shipment_lines_view_events_init'))) {
        var $modalContent = $view.findParentByClass('modal_content');

        if ($.isOk($modalContent)) {
            var modal_idx = parseInt($modalContent.data('idx'));
            bimpModal.addButton('<i class="fas fa5-save iconLeft"></i>Enregistrer', 'saveShipmentLines($(this), ' + $view.data('id_object') + ', ' + modal_idx + ')', 'primary', '', modal_idx);
        } else {
            $view.find('.commande_shipments_form').find('.buttonsContainer').show();
        }

        $view.data('shipment_lines_view_events_init', 1);
    }
}

function saveShipmentLines($button, id_shipment, modal_idx) {
    var $modal = $button.findParentByClass('modal-content');
    var $container = null;
    if ($.isOk($modal)) {
        var $container = $modal.find('#modal_content_' + modal_idx).find('.shipment_lines');
    }

    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue (Conteneur absent). Opération abandonnée');
        return;
    }

    var $rows = $container.find('tr.shipment_line_row');

    var lines = [];
    if ($rows.length) {
        $rows.each(function () {
            var $row = $(this);
            var data = {};
            data.id_line = parseInt($row.data('id_line'));
            data.qty = parseFloat($row.find('[name="line_' + data.id_line + '_qty"]').val());
            if (isNaN(data.qty)) {
                bimp_msg('Quantités invalides pour la n°' + $row.data('num_line') + '<br/>Veuillez corriger', 'danger');
                return;
            }
            var $groupInput = $row.find('[name="line_' + data.id_line + '_group_article"]');
            if ($groupInput.length) {
                data.group = parseInt($groupInput.val());
            }

            var $entrepotInput = $row.find('[name="line_' + data.id_line + '_id_entrepot"]');
            if ($entrepotInput.length) {
                data.id_entrepot = parseInt($entrepotInput.val());
            }

            var $equipments = $container.find('#shipment_line_' + data.id_line + '_equipments');
            if ($equipments.length) {
                data.equipments = [];
                $equipments.find('.line_' + data.id_line + '_equipments_check:checked').each(function () {
                    data.equipments.push(parseInt($(this).val()));
                });
            }
            lines.push(data);
        });

        var $resultContainer = $container.find('div.ajaxResultContainer');

        setObjectAction($button, {
            module: 'bimplogistique',
            object_name: 'BL_CommandeShipment',
            id_object: id_shipment
        }, 'saveLines', {
            lines: lines
        }, null, $resultContainer, function () {
            bimpModal.removeContent(modal_idx);
        });
    } else {
        bimp_msg('Aucune ligne à enregistrer', 'warning');
    }
}

//function onShipmentEquipmentFormEquipmentsLoaded($form) {
//    if ($.isOk($form)) {
//        $form.find('.line_equipments_container').each(function () {
//            var $container = $(this);
//            if (!parseInt($container.data('line_equipments_container_events_init'))) {
//                var id_line = parseInt($container.data('id_line'));
//                var $input = $container.find('input[name="line_' + id_line + '_qty"]');
//
//                if ($input.length) {
//                    $input.change(function () {
//                        var qty = parseInt($(this).val());
//                        var min = parseInt($(this).data('min'));
//                        var max_equipments = qty - min;
//
//                        $container.find('.max_nb_equipments').text(max_equipments);
//                        checkShipmentEquipmentsFormEquipmentsNumber($container);
//                    });
//                }
//
//                $container.find('input.equipments_check').each(function () {
//                    $(this).change(function () {
//                        checkShipmentEquipmentsFormEquipmentsNumber($(this).findParentByClass('line_equipments_container'));
//                    });
//                });
//
//                checkShipmentEquipmentsFormEquipmentsNumber($container);
//                $container.data('line_equipments_container_events_init', 1);
//            }
//        });
//    }
//}
//
//function checkShipmentEquipmentsFormEquipmentsNumber($container) {
//    var $selected = $container.find('input.equipments_check:checked');
//    var max = parseInt($container.find('.max_nb_equipments').text());
//
//    var remain = max - $selected.length;
//
//    var msg = '';
//    var className = '';
//    var check = true;
//
//    if (remain > 0) {
//        msg = 'Il reste ' + remain + ' équipement(s) à sélectionner';
//        className = 'warning';
//    } else if (remain < 0) {
//        msg = 'Vous devez désélectionner ' + (-remain) + ' équipement(s)';
//        className = 'danger';
//        check = false;
//    } else {
//        msg = 'Il ne reste plus aucun équipement à sélectionner';
//        className = 'success';
//    }
//
//    var html = '<div class="alert alert-' + className + '">' + msg + '</div>';
//    $container.find('.equipments_selection_infos').html(html);
//
//    var $modal_content = $container.findParentByClass('modal_content');
//    if ($.isOk($modal_content)) {
//        var modal_idx = parseInt($modal_content.data('idx'));
//        var $button = $modal_content.findParentByClass('modal-content').find('.modal-footer').find('button.set_action_button.modal_' + modal_idx);
//        if ($.isOk($button)) {
//            if (!check) {
//                $button.addClass('disabled');
//            } else {
//                $button.removeClass('disabled');
//            }
//        }
//    }
//}

function onShipmentEquipmentsFormSubmit($form, extra_data) {
    extra_data['lines'] = [];

    $form.find('.line_equipments_container').each(function () {
        var $container = $(this);
        var id_line = parseInt($container.data('id_line'));
        var qty = parseInt($container.find('input[name="line_' + id_line + '_qty"]').val());
        var equipments = [];

        $container.find('input.equipments_check:checked').each(function () {
            equipments.push(parseInt($(this).val()));
        });

        extra_data['lines'].push({
            id_line: id_line,
            qty: qty,
            equipments: equipments
        });
    });

    return extra_data;
}

function onShipmentFactureFormSubmit($form, extra_data) {
    var $container = $form.find('.shipment_facture_lines_inputs');
    var lines = [];

    if ($container.length) {
        var id_facture = parseInt($container.data('id_facture'));
        if (isNaN(id_facture)) {
            id_facture = 0;
        }

        var $rows = $container.find('tr.line_row');
        if ($rows.length) {
            $rows.each(function () {
                var id_line = parseInt($(this).data('id_line'));
                if (!isNaN(id_line) && id_line) {
                    var line = {
                        id_line: id_line,
                        qty: 0,
                        equipments: []
                    };
                    var $input = $(this).find('input.line_facture_qty');
                    if ($input.length) {
                        line.qty = parseFloat($input.val());
                    }
                    var $equipments_row = $container.find('#facture_line_' + id_line + '_equipments');
                    if ($equipments_row.length) {
                        $equipments_row.find('[name="line_' + id_line + '_facture_' + id_facture + '_equipments[]"]:checked').each(function () {
                            line.equipments.push(parseInt($(this).val()));
                        });
                    }
                }

                lines.push(line);
            });
        }
    }

    extra_data.lines = lines;

    return extra_data;
}

// Factures commandes client: 

function onCommandeLineFacturesViewLoaded($view) {
    if (!parseInt($view.data('line_factures_view_events_init'))) {
        var $modalContent = $view.findParentByClass('modal_content');

        if ($.isOk($modalContent)) {
            var modal_idx = parseInt($modalContent.data('idx'));
            bimpModal.addButton('<i class="fas fa5-save iconLeft"></i>Enregistrer', 'saveCommandeLineFactures($(this), ' + $view.data('id_object') + ')', 'primary', '', modal_idx);
        } else {
            $view.find('.commande_factures_form').find('.buttonsContainer').show();
        }

        $view.data('line_factures_view_events_init', 1);
    }
}

function saveCommandeLineFactures($button, id_line) {
    var $form = $('#commande_line_' + id_line + '_factures_form');

    if ($form.length) {
        var $rows = $form.find('tr.facture_row');

        var factures = [];
        if ($rows.length) {
            $rows.each(function () {
                var $row = $(this);
                var data = {};
                data.id_facture = parseInt($row.data('id_facture'));
                data.qty = parseFloat($row.find('input.line_facture_qty').val());
                if (isNaN(data.qty)) {
                    bimp_msg('Quantités invalides pour la facture "' + $row.data('facnumber') + '"<br/>Veuillez corriger', 'danger');
                    return;
                }
                data.equipments = [];

                $row.find('[name="line_' + id_line + '_facture_' + data.id_facture + '_equipments[]"]:checked').each(function () {
                    var id_equipment = parseInt($(this).val());
                    if (!isNaN(id_equipment) && id_equipment) {
                        data.equipments.push(id_equipment);
                    }
                });
                factures.push(data);
            });

            var $resultContainer = $form.find('div.ajaxResultContainer');

            setObjectAction($button, {
                module: 'bimpcommercial',
                object_name: 'Bimp_CommandeLine',
                id_object: id_line
            }, 'saveFactures', {
                factures: factures
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

function addSelectedCommandeLinesToFacture($button, list_id, id_commande, id_client_facture, id_contact, id_cond_reglement) {
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
        facture_lines_list: [],
        id_client_facture: id_client_facture,
        id_contact: id_contact,
        id_cond_reglement: id_cond_reglement
    };

    $selected.each(function () {
        extra_data.facture_lines_list.push(parseInt($(this).data('id_object')));
    });

    setObjectAction($button, {
        module: 'bimpcommercial',
        object_name: 'Bimp_Commande',
        id_object: id_commande
    }, 'linesFactureQties', extra_data, 'invoice', null, null, null, function ($form, extra_data) {
        return onFactureFormSubmit($form, extra_data);
    });
}

function onFactureFormSubmit($form, extra_data) {
    var lines = [];

    var $inputs = $form.find('.facture_lines_inputContainer').find('input.line_facture_qty');

    $inputs.each(function () {
        var id_line = parseInt($(this).data('id_line'));
        var qty = parseFloat($(this).val());
        var equipments = [];

        var $row = $form.find('#facture_line_' + id_line + '_equipments');
        if ($row.length) {
            $row.find('.check_list_item_input:checked').each(function () {
                equipments.push(parseInt($(this).val()));
            });
        }

        lines.push({
            id_line: id_line,
            qty: qty,
            equipments: equipments
        });
    });

    extra_data['lines'] = lines;
    return extra_data;
}

// Logistique commandes fournisseur: 

function onCommandeFournReceptionDetailsViewLoaded($view) {
    if (!$.isOk($view)) {
        return;
    }
    var $container = $view.find('.reception_details');
    if (!$container.length) {
        return;
    }

    if (!parseInt($container.data('edit'))) {
        return;
    }

    if (!parseInt($view.data('reception_view_events_init'))) {
        var $modalContent = $view.findParentByClass('modal_content');

        if ($.isOk($modalContent)) {
            var modal_idx = parseInt($modalContent.data('idx'));
            bimpModal.addButton('Valider<i class="fas fa5-arrow-circle-right iconRight"></i>', 'saveCommandeFournReceptionLinesData($(this), ' + $view.data('id_object') + ', ' + modal_idx + ')', 'primary', '', modal_idx);
        }

        $view.data('reception_view_events_init', 1);
    }
}

function addCommandeFournReceptionLineQtyRow($button, id_line) {
    var $container = $button.findParentByClass('line_' + id_line + '_qty_input_container');
    if (!$.isOk($container)) {
        bimp_msg('Erreur (conteneur absent', 'danger');
        return;
    }

    var tpl = $container.find('tr.line_' + id_line + '_qty_row_tpl').html();
    if (!tpl) {
        bimp_msg('Erreur (template absent)', 'danger');
        return;
    }

    var idx = parseInt($container.find('tr.line_' + id_line + '_qty_row').last().data('qty_idx'));
    if (isNaN(idx)) {
        idx = 0;
    }

    idx++;

    tpl = tpl.replace(/qtyidx/g, idx);

    var html = '<tr class="line_' + id_line + '_qty_row line_qty_row" data-qty_idx="' + idx + '">';
    html += tpl;
    html += '<tr>';

    $container.find('tbody.line_' + id_line + '_qty_rows').append(html);

    var $new_row = $container.find('tr.line_' + id_line + '_qty_row').last();

    setCommonEvents($new_row);
    setInputsEvents($new_row);

    var $input = $new_row.find('input.qtyInput');
    if ($input.length) {
        checkTotalMaxQtyInput($input);
    }
}

function getReceptionLinesDataFromForm($content, id_reception) {
    var lines = [];

    if ($.isOk($content)) {
        $content.find('tr.line_row').each(function () {
            var $row = $(this);
            var id_line = parseInt($row.data('id_line'));
            var serialisable = parseInt($row.data('serialisable'));

            if (serialisable) {
                var serials = [];
                $row.find('tr.line_' + id_line + '_serial_data').each(function () {
                    var serial = $(this).find('td.serial').data('serial');
                    var pu_ht = 0;
                    var tva_tx = 0;

                    var $input = $(this).find('[name="line_' + id_line + '_reception_' + id_reception + '_serial_' + serial + '_pu_ht"]');
                    if ($.isOk($input)) {
                        pu_ht = parseFloat($input.val());
                    }

                    $input = $(this).find('[name="line_' + id_line + '_reception_' + id_reception + '_serial_' + serial + '_tva_tx"]');
                    if ($.isOk($input)) {
                        tva_tx = parseFloat($input.val());
                    }
                    serials.push({
                        serial: serial,
                        pu_ht: pu_ht,
                        tva_tx: tva_tx
                    });
                });

                var new_serials = '';

                var $input = $row.find('[name="line_' + id_line + '_reception_' + id_reception + '_new_serials"]');
                if ($input.length) {
                    new_serials = $input.val();
                }

                var new_serials_pu_ht = 0;
                var $input = $row.find('[name="line_' + id_line + '_reception_' + id_reception + '_new_serials_pu_ht"]');
                if ($input.length) {
                    new_serials_pu_ht = parseFloat($input.val());
                }

                var new_serials_tva_tx = 0;
                var $input = $row.find('[name="line_' + id_line + '_reception_' + id_reception + '_new_serials_tva_tx"]');
                if ($input.length) {
                    new_serials_tva_tx = parseFloat($input.val());
                }

                var assign_to_commande_client = 0;
                var $input = $row.find('[name="line_' + id_line + '_reception_' + id_reception + '_assign_to_commande_client"]');
                if ($input.length) {
                    assign_to_commande_client = parseInt($input.val());
                }

                lines.push({
                    id_line: id_line,
                    serials: serials,
                    new_serials: new_serials,
                    new_serials_pu_ht: new_serials_pu_ht,
                    new_serials_tva_tx: new_serials_tva_tx,
                    assign_to_commande_client: assign_to_commande_client
                });
            } else {
                var qties = [];
                $row.find('tr.line_' + id_line + '_qty_row').each(function () {
                    var idx = parseInt($(this).data('qty_idx'));
                    var qty = 0;
                    var pu_ht = 0;
                    var tva_tx = 0;

                    var $input = $(this).find('[name="line_' + id_line + '_reception_' + id_reception + '_qty_' + idx + '_qty"]');
                    if ($.isOk($input)) {
                        qty = parseFloat($input.val());
                    }

                    var $input = $(this).find('[name="line_' + id_line + '_reception_' + id_reception + '_qty_' + idx + '_pu_ht"]');
                    if ($.isOk($input)) {
                        pu_ht = parseFloat($input.val());
                    }

                    $input = $(this).find('[name="line_' + id_line + '_reception_' + id_reception + '_qty_' + idx + '_tva_tx"]');
                    if ($.isOk($input)) {
                        tva_tx = parseFloat($input.val());
                    }
                    qties.push({
                        qty: qty,
                        pu_ht: pu_ht,
                        tva_tx: tva_tx
                    });
                });

                var assign_to_commande_client = 0;
                var $input = $row.find('[name="line_' + id_line + '_reception_' + id_reception + '_assign_to_commande_client"]');
                if ($input.length) {
                    assign_to_commande_client = parseInt($input.val());
                }

                lines.push({
                    id_line: id_line,
                    qties: qties,
                    assign_to_commande_client: assign_to_commande_client
                });
            }
        });
    }

    return lines;
}

function saveCommandeFournReceptionLinesData($button, id_reception, modal_idx) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $modal = $button.findParentByClass('modal');
    if (!$.isOk($modal)) {
        bimp_msg('Erreur (Modale absente)', 'danger');
        return;
    }

    var $content = $modal.find('#modal_content_' + modal_idx);
    if (!$.isOk($content)) {
        bimp_msg('Erreur (Contenu non trouvé)', 'danger');
        return;
    }

    var lines = getReceptionLinesDataFromForm($content, id_reception);

    setObjectAction($button, {
        module: 'bimplogistique',
        object_name: 'BL_CommandeFournReception',
        id_object: id_reception
    }, 'saveLinesData', {
        lines: lines
    }, null, $content.find('.ajaxResultContainer'));
}

function onCommandeFournLineReceptionViewLoaded($view) {
    if (!parseInt($view.data('line_reception_view_events_init'))) {
        var $modalContent = $view.findParentByClass('modal_content');

        if ($.isOk($modalContent)) {
            var modal_idx = parseInt($modalContent.data('idx'));
            bimpModal.addButton('Valider<i class="fas fa5-arrow-circle-right iconRight"></i>', 'addCommandeFournLineReceptions($(this), ' + $view.data('id_object') + ', ' + modal_idx + ')', 'primary', '', modal_idx);
        }

        $view.data('line_reception_view_events_init', 1);
    }
}

function addCommandeFournLineReceptions($button, id_line, modal_idx) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $container = $('#modal_content_' + modal_idx).find('#commande_fourn_line_' + id_line + '_receptions_rows');

    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue (conteneur absent). Opération abandonnée', 'danger');
        return;
    }

    var rows_data = [];
    var assign_to_commande_client = 0;

    var $input = $container.find('[name="assign_to_commande_client"]');
    if ($input.length) {
        assign_to_commande_client = parseInt($input.val());
    }

    var i = 1;
    var check = true;
    $container.find('tbody.receptions_rows').find('tr.line_reception_row').each(function () {
        var idx = parseInt($(this).data('idx'));
        if (idx) {
            var id_reception = 0;
            var $input = $(this).find('[name="line_' + id_line + '_reception_' + idx + '_id_reception"]');
            if ($input.length) {
                id_reception = parseInt($input.val());
            }

            if (!id_reception) {
                bimp_msg('Aucune réception sélectionnée pour la réception n°' + i, 'danger');
                check = false;
            }

            var row_data = {
                'id_reception': id_reception,
                'qty': 0,
                'serials': '',
                'pu_ht': null,
                'tva_tx': null
            };

            $input = $(this).find('[name="line_' + id_line + '_reception_' + idx + '_equipments"]');
            if ($input.length) {
                row_data['serials'] = $input.val();
            }

            $input = $(this).find('[name="line_' + id_line + '_reception_' + idx + '_qty"]');
            if ($input.length) {
                row_data['qty'] = parseFloat($input.val());
            }

            $input = $(this).find('[name="line_' + id_line + '_reception_' + idx + '_pu_ht"]');
            if ($input.length) {
                row_data['pu_ht'] = parseFloat($input.val());
            }

            $input = $(this).find('[name="line_' + id_line + '_reception_' + idx + '_tva_tx"]');
            if ($input.length) {
                row_data['tva_tx'] = parseFloat($input.val());
            }

            row_data['assign_to_commande_client'] = assign_to_commande_client;

            rows_data.push(row_data);
            i++;
        }
    });

    if (check) {
        var $resultContainer = $container.find('.ajaxResultsContainer');

        setObjectAction($button, {
            module: 'bimpcommercial',
            object_name: 'Bimp_CommandeFournLine',
            id_object: id_line
        }, 'addReceptions', rows_data, null, $resultContainer, function () {
            var $modalContent = $container.findParentByClass('modal_content');
            if ($.isOk($modalContent)) {
                var modal_idx = parseInt($modalContent.data('idx'));
                bimpModal.removeContent(modal_idx);
            }
        }, null, null);
    }
}

function addCommandeFournLineReceptionRow($button, id_line) {
    var $container = $button.findParentByClass('line_reception_rows');

    if (!$.isOk($container) || parseInt($container.data('id_line')) !== id_line) {
        bimp_msg('une erreur est survenue');
        return;
    }

    var $tpl = $container.find('.line_reception_row_tpl');
    var idx = parseInt($tpl.data('next_idx'));

    var html = '<tr class="line_' + id_line + 'reception_row line_reception_row" data-idx="' + idx + '">';
    var tpl_html = $tpl.html().replace(/receptionidx/g, idx);
    tpl_html = tpl_html.replace(/linetotalmaxinputclass/g, 'line_' + id_line + '_reception_max');
    html += tpl_html;
    html += '<td style="text-align: right">';
    html += '<span class="rowButton" onclick="removeCommandeFournLineReceptionRow($(this), ' + id_line + ');">';
    html += '<i class="fas fa5-trash"></i></span></td>';
    html += '</tr>';

    $container.find('tbody.receptions_rows').append(html);
    $tpl.data('next_idx', idx + 1);

    setInputsEvents($container);

    checkTotalMaxQtyInput($container.find('input[name="line_' + id_line + '_reception_' + idx + '_qty"]'));
}

function removeCommandeFournLineReceptionRow($button, id_line) {
    var $container = $button.findParentByClass('line_reception_rows');

    if (!$.isOk($container)) {
        bimp_msg('une erreur est survenue');
        return;
    }

    $button.parent('td').parent('tr').remove();
    checkTotalMaxQtyInput($container.find('input[name="line_' + id_line + '_reception_1_qty"]'));
}

function onReceptionValidationFormSubmit($form, extra_data) {
    if ($.isOk($form)) {
        var id_reception = parseInt($form.data('id_object'));
        if (id_reception) {
            extra_data['lines'] = getReceptionLinesDataFromForm($form, id_reception);
        }
    }

    return extra_data;
}

function setAllReceptionLinesToMax($button) {
    var $container = $button.findParentByClass('reception_details');

    if (!$.isOk($container)) {
        bimp_msg('Erreur (conteneur absent)', 'danger');
        return;
    }

    $container.find('tr.line_qty_row').each(function () {
        $(this).find('.qtyInput').each(function () {
            var max = parseFloat($(this).data('max'));
            if (!isNaN(max)) {
                $(this).val(max).change();
            }
        });
    });
}

$(document).ready(function () {
    $('body').on('viewLoaded', function (e) {
        if (e.$view.hasClass('Bimp_CommandeLine_view_shipments')) {
            onCommandeLineShipmentsViewLoaded(e.$view);
        } else if (e.$view.hasClass('Bimp_CommandeLine_view_invoices')) {
            onCommandeLineFacturesViewLoaded(e.$view);
        } else if (e.$view.hasClass('BL_CommandeShipment_view_lines')) {
            onShipmentLinesViewLoaded(e.$view);
        } else if (e.$view.hasClass('BL_CommandeFournReception_view_details')) {
            onCommandeFournReceptionDetailsViewLoaded(e.$view);
        } else if (e.$view.hasClass('Bimp_CommandeFournLine_view_reception')) {
            onCommandeFournLineReceptionViewLoaded(e.$view);
        }
    });

    $('body').on('inputReloaded', function (e) {
        if (e.input_name === 'equipments' && $.isOk(e.$form) && e.$form.hasClass('Bimp_Commande_form_shipment_equipments')) {
            onShipmentEquipmentFormEquipmentsLoaded(e.$form);
        }
    });

    $('body').on('listLoaded', function (e) {
        if (e.$list.hasClass('Bimp_CommandeLine_list_table_logistique')) {
            onCommandeLinesLogistiqueListLoaded(e.$list);
        }
    });
    $('body').on('listLoaded', function (e) {
        if (e.$list.hasClass('Bimp_CommandeLine_list_table_logistique')) {
            onCommandeLinesLogistiqueListLoaded(e.$list);
            e.$list.on('listRefresh', function () {
                onCommandeLinesLogistiqueListLoaded($(this));
            });
        }
    });
});