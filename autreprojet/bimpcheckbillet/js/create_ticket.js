

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
                $(".chosen-select").chosen({no_results_text: 'Pas de résultat'});
                initEvents();
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
                $(".chosen-select").trigger("chosen:updated");
            } else {
                setMessage('alertSubmit', "Erreur serveur 5851.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}


function createTicket(id_event, id_tariff, price, extra_int1, extra_int2, extra_string1, extra_string2) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            id_tariff: id_tariff,
            price: price,
            extra_int1: extra_int1,
            extra_int2: extra_int2,
            extra_string1: extra_string1,
            extra_string2: extra_string2,
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
                $('input[name=price]').val(),
                $('input[name=extra_int1]').val(),
                $('input[name=extra_int2]').val(),
                $('input[name=extra_string1]').val(),
                $('input[name=extra_string2]').val());
    });
}



