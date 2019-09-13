
// Ajax call
function insertProduct(input, quantity) {

    BimpAjax('insertInventoryLine', {
        input: input,
        quantity: quantity
    }, null, {
        processing_msg: 'Insertion en cours',
        success: function (result, bimpAjax) {
            $("input[name=search_insert_line]").val('');
            $("input[name=search_insert_line]").focus();
            $("input[name=insert_quantity]").val(1);
            triggerObjectChange('bimplogistique', 'InventoryLine', result.data.id_inventory_det);
        }
    });
}


/**
 * Functions
 */

function initEvents() {
    var $inputs_selector = $("input[name*=insert_]");
    $inputs_selector.on('keydown', function (event) {
        var key = event.which;
        if (key === 9 || key === 13) {
            event.preventDefault(event);
            var input = $("input[name=search_insert_line]").val();
            var quantity = $("input[name=insert_quantity]").val();
            insertProduct(input, quantity);
        }
    });
}

var waitForElement = function (selector, callback, count) {
    if ($(selector).length) {
        callback();
    } else {
        setTimeout(function () {
            if (!count) {
                count = 0;
            }
            count++;
            if (count < 100) {
                waitForElement(selector, callback, count);
            } else {
                return;
            }
        }, 100);
    }
};



// Ready
$(document).ready(function () {
    waitForElement('input[name=search_insert_line]', function () {
        waitForElement('input[name=insert_quantity]', function () {
            initEvents();
        });
    });

});