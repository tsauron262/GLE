
/**
 * Global variable
 */

var events;
var tariffs;
var attributes;

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
                getAttributeAll();
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

function getAttributeAll() {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_all_attributes'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 9472.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.attributes.length > 0) {
                attributes = out.attributes;
                out.attributes.forEach(function (attribute) {
                    $('select[name=attribute]').append(
                            '<option value=' + attribute.id + '>' + attribute.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
            } else {
                setMessage('alertSubmit', "Merci de créer des attributs avant de les lier.", 'warn');
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

function link(id_tariff, id_attribute) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            id_attribute: id_attribute,
            action: 'link_attribute_tariff'
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
        var id_attribute = $('select[name=attribute] > option:selected').val();
        if (id_tariff > 0 && id_attribute > 0) {
            link(id_tariff, id_attribute);
        } else if (!(id_tariff > 0) && !(id_attribute > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner un tarif et une déclibaison.", 'error');
        } else if (!(id_attribute > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner une attribut.", 'error');
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
        $('select[name=attribute] > option').each(function (id_attr) {
            var option = $(this);
            tariff.attributes.forEach(function (attribute) {
                if (parseInt(attribute.id) === parseInt(id_attr))
                    option.prop('disabled', false);
                else
                    option.prop('disabled', true);
            });
        });
        $("select[name=attribute]").trigger("chosen:updated");

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