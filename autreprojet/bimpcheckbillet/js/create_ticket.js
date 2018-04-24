
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
                $('select[name=tariff]').append('<option value="">Sélectionnez</option>');
                out.tariffs.forEach(function (tariff) {
                    $('select[name=tariff]').append(
                            '<option value=' + tariff.id + '>'
                            + tariff.label + ' ' +
                            +tariff.price + ' €</option>');
                });
                $(".chosen-select").trigger("chosen:updated");
                tariffs = out.tariffs;
                initEventChangeTariff(tariffs);
            } else {
                setMessage('alertSubmit', "Veuillez créer des tariffs avant de réserver des tickets.", 'error');
                $('button[name=create]').hide();
            }
        }
    });
}


function createTicket(id_event, id_tariff, price, first_name, last_name, extra_1, extra_2, extra_3, extra_4, extra_5, extra_6) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            id_tariff: id_tariff,
            price: price,
            first_name: first_name,
            last_name: last_name,
            extra_1: extra_1,
            extra_2: extra_2,
            extra_3: extra_3,
            extra_4: extra_4,
            extra_5: extra_5,
            extra_6: extra_6,
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
                $('input[name=first_name]').val(),
                $('input[name=last_name]').val(),
                $('input[name=extra_1]').val(),
                $('input[name=extra_2]').val(),
                $('input[name=extra_3]').val(),
                $('input[name=extra_4]').val(),
                $('input[name=extra_5]').val(),
                $('input[name=extra_6]').val());
    });
}


function initEventChangeTariff(tariffs) {
    $('select[name=tariff]').change(function () {
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        tariffs.forEach(function (tariff) {
            if (tariff.id === parseInt(id_tariff))
                addExtras(tariff);
        });
    });
}


function addExtras(tariff) {

    $('#extra').empty();
    var html = '';

    for (var i = 1; i <= 6; i++) {
        html += addInput(tariff, i);
    }

    $('#extra').append(html);
}

function addInput(tariff, id) {

    var html = '';
    var field_type = 'type_extra_' + id;
    var field_name = 'name_extra_' + id;
    var input_name = 'extra_' + id;

    if (tariff[field_type] !== 0 && tariff[field_name] !== null) { // defined
        html += '<label for="' + field_name + '">' + tariff[field_name] + ' </label><br/>';
        if (tariff[field_type] === 1) { // int
            html += '<input class="form-control bfh-number" name="' + input_name + '" step="1" type="number" style="width: 120px"/><br/>';
        } else if (tariff[field_type] === 2) { // float
            html += '<input class="form-control bfh-number" name="' + input_name + '" step="0.01" type="number" style="width: 120px"/><br/>';
        } else { // string
            html += '<input class="form-control" placeholder="' + tariff[field_name] + '" name="' + input_name + '" maxlength=256 style="width: 300px"><br/>';
        }
    } else { // undefined
        html += '<input name="' + input_name + '" style="display: none">';
    }
    return html;
}