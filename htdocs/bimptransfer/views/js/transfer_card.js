

// Ajax call

function insertProduct(input, quantity) {
    BimpAjax('insertTransferLine', {
        input: input,
        quantity: quantity
    }, $('#sav_repairs').children('.panel-body'), {
        processing_msg: 'Insertion en cours',
        success: function (result, bimpAjax) {
            var id_affected = result.data.id_affected; // New id or modified id
            reloadTransfertLine(id_affected);
        }
    });
}

// Ready
$(document).ready(function () {
    initEvents();
});


/**
 * Functions
 */

function initEvents() {
    var $inputs_selector = $("input[name^=insert_]");
    $inputs_selector.click(function (e) {
        e.stopPropagation();
    });

    $inputs_selector.keypress(function (e) {
        var key = e.which;
        if (key == 13) {
            insertProduct($('input[name=insert_line]').val(), $('input[name=insert_quantity]').val());
        }
    });
}

function reloadTransfertLines() {
    $('tr.TransferLine_row.objectListItemRow').each(function(){
        reloadTransfertLine($(this).attr('data-id_object'));
    });
}

function reloadTransfertLine(id) {
    $('body').trigger($.Event('objectChange', {
        module: 'bimptransfer',
        object_name: 'TransferLine',
        id_object: id
    }));
}