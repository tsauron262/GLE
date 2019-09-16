
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
            playBipOk();
        }, error: function(result, bimpAjax) {
            playBipError();
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
    // Allow sound
    $('div#allow_sound').click(function(){
        var audio_ok = new Audio(DOL_URL_ROOT + '/bimplogistique/views/sound/bip_ok.mp3');
        audio_ok.play();
        var audio_error = new Audio(DOL_URL_ROOT + '/bimplogistique/views/sound/bip_error.mp3');
        audio_error.play();
    });
    
    waitForElement('input[name=search_insert_line]', function () {
        waitForElement('input[name=insert_quantity]', function () {
            initEvents();
        });
    });

});

function playBipOk() {
    var audio_ok = new Audio(DOL_URL_ROOT + '/bimplogistique/views/sound/bip_ok.mp3');
    audio_ok.play();
}

function playBipError() {
    var audio_error = new Audio(DOL_URL_ROOT + '/bimplogistique/views/sound/bip_error.mp3');
    audio_error.play();
}