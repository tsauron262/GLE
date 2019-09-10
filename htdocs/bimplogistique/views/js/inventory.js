

// Ajax call
function insertProduct(input, quantity) {

    BimpAjax('insertInventoryLine', {
        input: input,
        quantity: quantity
    }, null, {
        processing_msg: 'Insertion en cours',
        success: function (result, bimpAjax) {
            reloadInventoryLine(result.data.id_inventory_det);
            reloadProductList(result.id_product);
            reloadEquipmentList(result.id_equipment);
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
    var $inputs_selector = $("input[name*=insert_]");

    $inputs_selector.on('keyup mouseup', function (e) {
        var key = e.which;
        if (key === 9 || key === 13) {
            e.preventDefault();
            var input = $("input[name=search_insert_line]").val();
            var quantity = $("input[name=insert_quantity]").val();
            insertProduct(input, quantity);
        }
    });
}

function reloadInventoryLines() {
    $('tr.InventoryLine_row.objectListItemRow').each(function () {
        reloadInventoryLine($(this).attr('data-id_object'));
    });
}

function reloadInventoryLine(id) {
    $('body').trigger($.Event('objectChange', {
        module: 'bimplogistique',
        object_name: 'InventoryLine',
        id_object: id
    }));
}

function reloadProductList(id) {
    $('body').trigger($.Event('objectChange', {
        module: 'bimpcore',
        object_name: 'Bimp_Product',
        id_object: id
    }));
}

function reloadEquipmentList(id) {
    $('body').trigger($.Event('objectChange', {
        module: 'bimpequipment',
        object_name: 'Equipment',
        id_object: 0
    }));
}