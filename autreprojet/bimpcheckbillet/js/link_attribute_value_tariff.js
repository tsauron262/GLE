
/**
 * Global variable
 */

var URL_PRESTASHOP = URL_PRESTA + '/modules/zoomdici/ajax.php';

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

function linkPrestashop(price, qty, id_tariff, id_attribute_value, id_prod_extern, id_attribute_value_extern) {

    $.ajax({
        type: "POST",
        url: URL_PRESTASHOP,
        data: {
            qty: qty,
            price: price,
            id_prod_extern: id_prod_extern,
            id_attribute_value_extern: id_attribute_value_extern,
            action: 'linkProductAttributeValue'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6158.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.is_ok) {
                link(price, qty, id_tariff, id_attribute_value)
            } else if (!out.is_ok) {
                setMessage('alertSubmit', "Cette valeur d'attribut a déjà été associée à ce tarif, rien n'a été fait.", 'warn');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 6218.', 'error');
            }
        }
    });
}

function link(price, qty, id_tariff, id_attribute_value) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            qty: qty,
            price: price,
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
                alert("Liaison créée, la page va se recharger.");
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
        var id_tariff = parseInt($('select[name=tariff] > option:selected').val());
        var id_attribute_value = parseInt($('select[name=attribute_value] > option:selected').val());
        if (id_tariff > 0 && id_attribute_value > 0) {
            var tariff = getTariff(id_tariff);
            var attribute_value = getAttributeValue(id_attribute_value);
            linkPrestashop(
                    parseFloat($('input[name=price]').val()),
                    parseInt($('input[name=number_place]').val()),
                    id_tariff,
                    id_attribute_value,
                    tariff.id_prod_extern,
                    attribute_value.id_attribute_value_extern);
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

function getAttributeValue(id_attribute_value) {
    var attribute_value_return;
    attribute_values.forEach(function (attribute_value) {
        if (parseInt(attribute_value.id) === id_attribute_value)
            attribute_value_return = attribute_value;
    });
    return attribute_value_return;
}