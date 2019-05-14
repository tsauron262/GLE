function setReservationStatus($button, id_reservation, status, doConfirm) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof (doConfirm) === 'undefined') {
        doConfirm = true;
    }

    if (doConfirm && parseInt(status) === 303) {
        if (!confirm('Etes-vous sûr de vouloir annuler cette réservation?')) {
            return;
        }
    }

    if (doConfirm && parseInt(status) === 0) {
        if (!confirm('Etes-vous sûr de vouloir réinitialiser cette réservation?')) {
            return;
        }
    }


    BimpAjax('setReservationStatus', {
        id_reservation: id_reservation,
        status: status
    }, null, {
        $button: $button,
        id_reservation: id_reservation,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: bimpAjax.id_reservation
            }));
        },
        error: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: bimpAjax.id_reservation
            }));
        }
    });
}

function setSelectedReservationStatus($button, list_id, status) {
    if (status === 303) {
        if (!confirm('Etes-vous sûr de vouloir annuler les réservations sélectionnées ?')) {
            return;
        }
    } else if (status === 0) {
        if (!confirm('Etes-vous sûr de vouloir réintialiser les réservations sélectionnées ?')) {
            return;
        }
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var $resultContainer = $('#' + list_id + '_result');
    var $selected = $list.find('tbody').find('input.item_check:checked');

    if (!$selected.length) {
        bimp_msg('Aucune réservation sélectionnée', 'danger');
        return;
    }

    $selected.each(function () {
        var done = false;
        var $row = $(this).findParentByClass('BR_Reservation_row');
        var id_reservation = $row.data('id_object');
        if ($row.length) {
            $row.find('.newStatusButton').each(function () {
                if (!done && parseInt($(this).data('new_status')) === parseInt(status)) {
                    setReservationStatus($(this), id_reservation, status, false);
                    done = true;
                }
            });
            if (!done) {
                bimp_msg('Ce statut ne peut pas être attribué à la réservation ' + id_reservation, 'warning');
            }
        }
    });
}

function findEquipmentToReceive($button, id_commande_client) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var serial = $('#findEquipmentSerial').val();
    if (!serial) {
        bimp_msg('Veuillez saisir un numéro de série', 'danger');
        return;
    }

    var $resultContainer = $('#equipmentForm').find('.freeFormAjaxResult');

    BimpAjax('findEquipmentToReceive', {
        id_commande_client: id_commande_client,
        serial: serial
    }, $resultContainer, {
        $button: $button,
        success: function (result, bimpAjax) {
            $('#findEquipmentSerial').val('');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: result.id_reservation
            }));
        }
    });
}

function removeFromCommandeFournisseur($button, id_reservation_cmd_fourn, force_remove) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof (force_remove) === 'undefined') {
        force_remove = false;
    }

    if (force_remove) {
        var msg = 'La commande fournisseur est validée et ne peut plus être modifiée. Veuillez confirmer le retrait forcé.' + "\n";
        msg += '(L\'article ne sera pas supprimée de la commande mais la réservation ne sera plus associée à celle-ci)';
        if (!confirm(msg)) {
            return;
        }
    }

    BimpAjax('removeFromCommandeFournisseur', {
        'id_reservation_cmd_fourn': id_reservation_cmd_fourn,
        'force_remove': force_remove
    }, null, {
        $button: $button,
        id_reservation_cmd_fourn: id_reservation_cmd_fourn,
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_ReservationCmdFourn',
                id_object: bimpAjax.id_reservation_cmd_fourn
            }));
        }
    });
}

function hideEquipmentForm() {
    $('#equipmentForm').slideUp(250);
    $('#openEquipmentsFormButton').parent().slideDown(250);
}

function openEquipmentsForm() {
    $('#equipmentForm').slideDown(250);
    $('#openEquipmentsFormButton').parent().slideUp(250);
}

function createSelectedShipmentsInvoice() {
    bimp_msg('ici');
}

function onReserveEquipmentsFormLoaded($form) {
    if (!$.isOk($form)) {
        return;
    }

    if (!parseInt($form.data('reserve_equipment_events_init'))) {
        var $input = $form.find('[name="search_serial"]');
        if ($input.length) {

            $input.focus(function () {
                var $container = $(this).findParentByClass('addValueInputContainer');
                if ($.isOk($container)) {
                    var $select = $container.find('[name="equipments_add_value"]');
                    if ($select.length) {
                        $select.val('').change();
                    }
                }
            });
            $input.keyup(function (e) {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    e.stopPropagation();
                    var serial = $(this).val();
                    if (serial) {
                        var $container = $(this).findParentByClass('addValueInputContainer');
                        if ($.isOk($container)) {
                            $container.find('.addValueBtn').click();
                        }
                    }
                }
            });
            
            var $container = $input.findParentByClass('addValueInputContainer');
            if ($.isOk($container)) {
                var $btn = $container.find('.addValueBtn');
                $btn.removeAttr('onclick').click(function () {
                    var serial = $input.val();
                    if (serial) {
                        var $select = $container.find('[name="equipments_add_value"]');
                        if ($select.length) {
                            var done = false;
                            $select.find('option').each(function () {
                                if (serial === $(this).text()) {
                                    $input.val('');
                                    $select.val($(this).attr('value')).change();
                                    done = true;
                                }
                            });
                            if (!done) {
                                bimp_msg('Le numéro de série saisi n\'a pas été trouvé parmi les équipements disponibles', 'warning');
                                return;
                            }
                        }
                    }
                    addMultipleInputCurrentValue($btn, 'equipments_add_value', 'equipments_add_value', false);
                });
            }

            $input.focus();
        }
        $form.data('reserve_equipment_events_init', 1);
    }
}

$(document).ready(function () {
    $('#findEquipmentSerial').keyup(function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#findEquipmentButton').click();
        }
    }).keydown(function (e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            e.stopPropagation();
            $('#findEquipmentButton').click();
        }
    });

    $('#createShipmentButton').popover();
    $('#createShipmentButton').click(function () {
        $(this).popover('hide');
    });

    $('body').on('formLoaded', function (e) {
        if (e.$form.hasClass('BR_Reservation_form_reserve_equipments')) {
            onReserveEquipmentsFormLoaded(e.$form);
        }
    });
});