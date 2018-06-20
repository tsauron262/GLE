
/**
 * Global variable
 */

var events;
var tariffs;
var combinations;

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
                $(".chosen-select").chosen({
                    placeholder_text_single: 'Evènement',
                    no_results_text: 'Pas de résultat'});
                $('select[name=id_event]').change(function () {
                    changeEventSession($('select[name=id_event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=id_event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=id_event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $(".chosen-select").trigger("chosen:updated");
                        $('select[name=id_event]').trigger('change');
                    }
                }
                getCombinationAll();
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

function getCombinationAll() {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_all_combinations'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 9472.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.combinations.length > 0) {
                combinations = out.combinations;
                out.combinations.forEach(function (combination) {
                    $('select[name=combination]').append(
                            '<option value=' + combination.id + '>' + combination.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
            } else {
                setMessage('alertSubmit', "Merci de créer des déclinaison avant de les lier.", 'warn');
            }
        }
    });
}

function getTariffsForEvent(id_event) {

    $('div#alertSubmit').empty();

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'get_tariffs_for_event_with_combination'
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
                $('select[name=tariff] > option').remove();
                $('select[name=tariff]').append('<option value="">Sélectionnez un tariff</option>');
                out.tariffs.forEach(function (tariff) {
                    $('select[name=tariff]').append(
                            '<option value=' + tariff.id + '>' + tariff.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
            } else {
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'error');
            }
        }
    });
}

function link(id_tariff, id_combination) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            id_combination: id_combination,
            action: 'link_combination_tariff'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3459.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.id_inserted > 0) {
                alert("Liaison créée.");
                location.reload();
            } else {
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'error');
            }
        }
    });
}


$(document).ready(function () {
    getEvents();
});


function initEvents() {

    $('button[name=link]').click(function () {
        var id_tariff = $('select[name=tariff] > option:selected').val();
        var id_combination = $('select[name=combination] > option:selected').val();
        if (id_tariff > 0 && id_combination > 0) {
            link(id_tariff, id_combination);
        } else if (!(id_tariff > 0) && !(id_combination > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner un tarif et une déclibaison.", 'error');
        } else if (!(id_combination > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner une déclinaison.", 'error');
        } else if (!(id_tariff > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner un tarif.", 'error');
        }
    });

    $('select[name=id_event]').change(function () {
        $('select[name=tariff] > option').remove();
        var id_event = parseInt($(this).val());
        if (id_event > 0)
            getTariffsForEvent(id_event);
    });

    $('select[name=tariff]').change(function () {
        var id_tariff = parseInt($(this).find('option:selected').val());
        var tariff = getTariff(id_tariff);
        $('select[name=combination] > option').each(function () {
            if (tariff.combinations.indexOf($(this).val()))
//                console.log('disabled');

                $(this).prop('disabled', true);
            else
//                console.log('enabled');

                $(this).prop('disabled', false);
        });
        $("select[name=combination]").trigger("chosen:updated");

    });
}

function getTariff(id_tariff) {
    var tariff_return;
    tariffs.forEach(function (tariff) {
        if (parseInt(tariff.id) === id_tariff)
            tariff_return = tariff;
    });
    return tariff_return;
}