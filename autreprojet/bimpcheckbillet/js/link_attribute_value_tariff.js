
/**
 * Global variable
 */

var events;
var tariffs;
var attributes;
var attribute_values;

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
//                getAttributeAll();
            } else if (out.events.length === 0) {
                alert("Aucun évènement n'a été créée, vous allez être redirigé vers la page de création des évènements.");
                window.location.replace('../view/create_event.php');
            } else {
                setMessage('alertSubmit', "Erreur 3154.", 'error');
                $('button[name=lnik]').hide();
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
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'warn');
            }
        }
    });
}

function getAttributeForTariff(id_tariff) {

    $('div#alertSubmit').empty();

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            action: 'get_attributes_by_tariff_id'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3594.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.attributes.length > 0) {
                attributes = out.attributes;
                $('select[name=attribute] > option').remove();
                $('select[name=attribute]').append('<option value="">Sélectionnez un attributs</option>');
                out.attributes.forEach(function (attribute) {
                    $('select[name=attribute]').append(
                            '<option value=' + attribute.id + '>' + attribute.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
            } else {
                setMessage('alertSubmit', "Aucun attribut pour ce tariff.", 'warn');
            }
        }
    });
}

function getAttributeValueForAttribute(id_attribute) {

    $('div#alertSubmit').empty();

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_attribute_parent: id_attribute,
            action: 'get_attributes_value_by_parent_id'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6482.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.attribute_values.length > 0) {
                attribute_values = out.attribute_values;
                $('select[name=attribute_value] > option').remove();
                $('select[name=attribute_value]').append('<option value="">Sélectionnez une valeur d\'attributs</option>');
                out.attribute_values.forEach(function (attribute_value) {
                    $('select[name=attribute_value]').append(
                            '<option value=' + attribute_value.id + '>' + attribute_value.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
            } else {
                setMessage('alertSubmit', "Aucune valeur pour cet attribut pour ce tariff.", 'warn');
            }
        }
    });
}

function link(id_tariff, id_attribute_value) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_tariff: id_tariff,
            id_attribute_value: id_attribute_value,
            action: 'link_attribute_value_tariff'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 9613.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
                alert("Liaison créée.");
                location.reload();
            } else {
                setMessage('alertSubmit', "Aucun tariff pour cet évènement.", 'error');
            }
        }
    });
}


$(document).ready(function () {
//    alert(1);
    getEvents();
});


function initEvents() {

    $('button[name=link]').click(function () {
        var id_tariff = $('select[name=tariff] > option:selected').val();
        var id_attribute_value = $('select[name=attribute_value] > option:selected').val();
        if (id_tariff > 0 && id_attribute_value > 0) {
            link(id_tariff, id_attribute_value);
        } else if (!(id_tariff > 0) && !(id_attribute_value > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner un tarif et une valeur d'attribut.", 'error');
        } else if (!(id_attribute_value > 0)) {
            setMessage('alertSubmit', "Veuillez sélectionner une valeur d'attribut.", 'error');
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
        if (id_tariff > 0)
            getAttributeForTariff(id_tariff);
//        var tariff = getTariff(id_tariff);
//        $('select[name=attribute] > option').each(function (id_attr) {
//            if (parseInt(tariff.attributes.indexOf(id_attr)) === -1)
//                $(this).prop('disabled', false);
//            else
//                $(this).prop('disabled', true);
//        });
//        $("select[name=attribute]").trigger("chosen:updated");

    });

    $('select[name=attribute]').change(function () {
        var id_attribute = parseInt($(this).val());
        if (id_attribute > 0)
            getAttributeValueForAttribute(id_attribute);
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