
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
            playBipOk();
            triggerObjectChange('bimplogistique', 'InventoryLine', result.data.id_inventory_det);
        }, error: function(result, bimpAjax) {
            playBipError();
        }
    });
}


/**
 * Functions
 */

function initEvents() {
    $("input[name=insert_quantity]").css('border', 'SOLID 2px white');
    var $inputs_selector = $("input[name*=insert_]");
    $inputs_selector.on('keydown', function (event) {
        var key = event.which;
        if (key === 9 || key === 13) {
            event.preventDefault(event);
            var input = $("input[name=search_insert_line]").val();
            var quantity = $("input[name=insert_quantity]").val();
            
            if(-1000 < Number(input) && Number(input) < 1000) {
                $("input[name=search_insert_line]").val('');
                $("input[name=insert_quantity]").val(input);
                var time=0.6;
                $("input[name=insert_quantity]").css({
                    transition : 'border-color ' + time + 's ease-in-out',
                    "border-color": "orange"
                });
                setTimeout(function(){
                    $("input[name=insert_quantity]").css({
                        transition : 'border-color ' + time + 's ease-in-out',
                        "border-color": "white"
                    });
                }, time * 1000);

                return;
            }
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
            $('input[name=search_insert_line]').focus();
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