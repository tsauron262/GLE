var tariffs;
var events;

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
                events = out.events;
                out.events.forEach(function (event) {
                    $('select[name=id_event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
                });
                initEvents();
                $('select[name=id_event]').change(function () {
                    changeEventSession($('select[name=id_event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=id_event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=id_event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $('select[name=id_event]').trigger('change');
                    }
                }
                $(".chosen-select").trigger("chosen:updated");
            } else if (out.events.length === 0) {
                alert("Aucun évènement n'a été créée, vous allez être redirigé vers la page de création des évènements.");
                window.location.replace('../view/create_event.php');
            } else {
                setMessage('alertSubmit', "Erreur 3154.", 'error');
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
            action: 'get_tariffs_for_event_with_attribute'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.tariffs !== -1) {
                tariffs = out.tariffs;
                $('select[name=tariff]').append('<option value="">Sélectionnez un tariff</option>');
                out.tariffs.forEach(function (tariff) {
                    $('select[name=tariff]').append(
                            '<option value=' + tariff.id + '>' + tariff.label + '</option>');
                });
            } else {
                $('select[name=tariff]').append('<option value="">Sélectionnez un tariff</option>');
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'error');
            }
            $('select[name=tariff]').trigger('chosen:updated');
        }
    });
}

function donwloadTickets(id_event, id_tariff, with_num, num_start, number, format) {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            id_tariff: id_tariff,
            with_num: with_num,
            num_start: num_start,
            number: number,
            format: format,
            action: 'create_tickets_from_check'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 4756.', 'error');
        },
        beforeSend: function () {
            $('*').css('cursor', 'wait');
        },
        complete: function () {
            $('*').css('cursor', 'auto');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.code_return) > 0) {
                $('a[name=download]').css('display', 'inline');
            } else {
                setMessage('alertSubmit', "Erreur serveur 9143.", 'error');
            }
        }
    });
}


/**
 * ready
 */
$(document).ready(function () {
    $(".chosen-select").chosen();
    getEvents();
});



/**
 * Function
 */

function initEvents() {

    $('select[name=id_event]').change(function () {
        $('select[name=tariff]').empty();
        var id_event = $(this).val();
        if (id_event > 0)
            getTariffsForEvent(id_event);
    });

    $('input[name=with_num]').change(function () {
        if (parseInt($(this).val()) === 1) { // use custome image
            $('div#div_num_start').css('display', 'inline');
        } else {
            $('div#div_num_start').css('display', 'none');
        }
    });

    $('div[name=create]').click(function () {
        var id_event = parseInt($('select[name=id_event] > option:selected').val());
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        var with_num = parseInt($('input[name=with_num]:checked').val());
        var num_start = parseInt($('input[name=num_start]').val());
        var number = parseInt($('input[name=number]').val());
        var format = $('input[name=format]:checked').val();

        if (id_tariff > 0) {
            donwloadTickets(id_event, id_tariff, with_num, num_start, number, format);
        } else {
            setMessage('alertSubmit', "Veuillez sélectionner un tariff", 'error');
        }
    });
}