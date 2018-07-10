var URL_PRESTASHOP = URL_PRESTA + '/modules/zoomdici/ajax.php';

/**
 * Ajax
 */

function createPrestashopAttribute(label, type) {

    $.ajax({
        type: 'POST',
        url: URL_PRESTASHOP,
        data: {
            label: label,
            type: type,
            action: 'createAttributeGroup'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3257.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
                createAttribute(label, type, out.id_inserted);
            } else {
                setMessage('alertSubmit', "Erreur inconnue 2349.", 'error');
            }
        }
    });
}

function createAttribute(label, type, id_attribute_extern) {
    $.ajax({
        type: 'POST',
        url: '../interface.php',
        data: {
            label: label,
            type: type,
            id_attribute_extern: id_attribute_extern,
            action: 'create_attribute'
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
    initEvents()
});


function initEvents() {
    $('button[name=create]').click(function () {
        createPrestashopAttribute($('input[name=label]').val(),
                $('select[name=type] > option:selected').val());
    });

}