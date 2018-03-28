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

    $button.addClass('disabled');

    BimpAjax('setReservationStatus', {
        id_reservation: id_reservation,
        status: status
    }, null, {
        $button: $button,
        id_reservation: id_reservation,
        success: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: bimpAjax.id_reservation
            }));
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
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
    } else {
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
}

function findEquipmentToReceive($button, id_commande_client) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var serial = $('#findEquipmentSerial').val();
    if (!serial) {
        bimp_msg('Veuillez saisir un numéro de série', 'danger');
        $button.removeClass('disabled');
        return;
    }

    var $resultContainer = $('#equipmentForm').find('.freeFormAjaxResult');

    BimpAjax('findEquipmentToReceive', {
        id_commande_client: id_commande_client,
        serial: serial
    }, $resultContainer, {
        $button: $button,
        success: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
            $('#findEquipmentSerial').val('');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: result.id_reservation
            }));
        },
        error: function (result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
}

function removeFromCommandeFournisseur($button, id_reservation_cmd_fourn) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    BimpAjax('removeFromCommandeFournisseur', {
        'id_reservation_cmd_fourn': id_reservation_cmd_fourn
    }, null, {
        id_reservation_cmd_fourn: id_reservation_cmd_fourn,
        display_success_in_popup_only: true,
        display_errors_in_popup_only: true,
        success: function (result, bimpAjax) {
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_ReservationCmdFourn',
                id_object: bimpAjax.id_reservation_cmd_fourn
            }));
        },
        error: function (result, bimpAjax) {
            $button.removeClass('disabled');
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

function generateBL($button, id_commande) {
    
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
});