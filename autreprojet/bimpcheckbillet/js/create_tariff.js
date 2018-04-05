

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
            } else {
                setMessage('alertSubmit', "Créer un évènement avant de définir un tarif.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}

function createTariff(label, price, id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            label: label,
            price: price,
            id_event: id_event,
            action: 'create_tariff'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1492.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', "Le tariff a été créée.", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3285.', 'error');
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
    $('button[name=create]').click(function () {
        createTariff($('input[name=label]').val(),
                $('input[name=price]').val(),
                $('select[name=event] > option:selected').val());
    });
}



