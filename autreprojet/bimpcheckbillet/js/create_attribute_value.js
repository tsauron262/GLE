var URL_PRESTASHOP = URL_PRESTA + '/modules/zoomdici/ajax.php';
var attributes;

/**
 * Ajax
 */


function getAllAttributes() {
    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_all_attributes'
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
                out.attributes.forEach(function (attribute) {
                    $('select[name=attribute_parent]').append(
                            '<option value=' + attribute.id + '>' + attribute.label + '</option>');
                });
                $('.chosen-select').trigger('chosen:updated');
                initEvents();
            } else {
                setMessage('alertSubmit', "Merci de créer des attributs avant de les lier.", 'warn');
            }
        }
    });
}


function createPrestashopAttributeValue(label, id_id_attribute_parent_check, id_attribute_parent_presta) {

    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            label: label,
            id_attribute_parent: id_attribute_parent_presta,
            action: 'createAttributeValue'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 7561.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
                createAttributeValue(label, id_id_attribute_parent_check, out.id_inserted);
            } else {
                setMessage('alertSubmit', "Erreur serveur 3189.", 'error');
            }
        }
    });
}

function createAttributeValue(label, id_attribute_parent, id_attribute_value_extern) {
    $.ajax({
        type: 'POST',
        url: '../interface.php',
        data: {
            label: label,
            id_attribute_parent: id_attribute_parent,
            id_attribute_value_extern: id_attribute_value_extern,
            action: 'create_attribute_value'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 9842.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
                setMessage('alertSubmit', "Attribut créée", 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 7452.', 'error');
            }
        }
    });
}

/**
 * Ready
 */
$(document).ready(function () {
    $(".chosen-select").chosen({
        placeholder_text_single: 'type',
        no_results_text: 'Pas de résultat'});
    getAllAttributes();
});


function initEvents() {
    $('button[name=create]').click(function () {
        if ($('input[name=label]').val() === '') {
            setMessage('alertSubmit', "Veuillez rentrer un label.", 'error');
            return;
        } else if (!parseInt($('select[name=attribute_parent] > option:selected').val()) > 0) {
            setMessage('alertSubmit', "Veuillez sélectionner un attribut parent.", 'error');
            return;
        }
        var attribute_parent = getAttributeById(parseInt($('select[name=attribute_parent] > option:selected').val()));
        createPrestashopAttributeValue($('input[name=label]').val(),
                attribute_parent.id,
                attribute_parent.id_attribute_extern);
    });
}

function getAttributeById(id_attribute) {
    var return_attribute;
    attributes.forEach(function (attribute) {
        if (parseInt(attribute.id) === id_attribute)
            return_attribute = attribute;
    });
    return return_attribute;
}