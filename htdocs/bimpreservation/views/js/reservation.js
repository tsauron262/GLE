function setReservationStatus($button, id_reservation, status) {
    if ($button.hasClass('disabled')) {
        return;
    }
    
    if (parseInt(status) === 303) {
        if (!confirm('Etes-vous sûr de vouloir annuler cette réservation?')) {
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
        success: function(result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: bimpAjax.id_reservation
            }));
        }, 
        error: function(result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
        }
    });
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
        success: function(result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
            $('#findEquipmentSerial').val('');
            $('body').trigger($.Event('objectChange', {
                module: 'bimpreservation',
                object_name: 'BR_Reservation',
                id_object: result.id_reservation
            }));
        },
        error: function(result, bimpAjax) {
            bimpAjax.$button.removeClass('disabled');
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