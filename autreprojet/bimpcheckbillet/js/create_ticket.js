

/**
 * Ajax call
 */

function getEvents() {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_events'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.events.length !== 0) {
                out.events.forEach(function (event) {
                    $('select[name=event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
                });
                initEvents();
                $('select[name=event]').trigger('change');
            } else {
                setMessage('alertSubmit', "Créer un évènement avant de réserver une place.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}

function getTariffsForEvent(id_event) {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'get_tariffs_for_event'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 5896.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.tariffs.length !== 0) {
                $('select[name=tariff]').empty();
                out.tariffs.forEach(function (tariff) {
                    $('select[name=tariff]').append(
                            '<option value=' + tariff.id + '>'
                            + tariff.label + ' ' +
                            +tariff.price + ' €</option>');
                });
            } else {
                setMessage('alertSubmit', "Erreur serveur 5851.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}


function createTicket(id_event, id_tariff, id_client) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            id_tariff: id_tariff,
            id_client: id_client,
            action: 'create_ticket'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 7592.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "Le ticket a été créée.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3885.', 'error');
            }
        }
    });
}

/**
 * Ready
 */
$(document).ready(function () {
    getEvents();
});

/**
 * Function
 */

function initEvents() {
    /* Refresh tarif list */
    $('select[name=event]').change(function () {
        getTariffsForEvent($('select[name=event] > option:selected').val());
    });
    
    /* Create ticket */
    $('button[name=create]').click(function () {
        createTicket($('select[name=event] > option:selected').val(),
                $('select[name=tariff] > option:selected').val(),
                1); // TODO id utilisateur
    });
}



