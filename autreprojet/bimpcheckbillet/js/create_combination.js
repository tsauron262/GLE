/**
 * Ajax
 */

function createCombination(label, price, number_place) {
    $.ajax({
        type: 'POST',
        url: '../interface.php',
        data: {
            label: label,
            price: price,
            number_place: number_place,
            action: 'create_combination'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 9842.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (parseInt(out.id_inserted) > 0) {
                setMessage('alertSubmit', "Déclinaison créée.", 'msg');
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
    initEvents()
});


function initEvents() {
    $('button[name=create]').click(function () {
        createCombination($('input[name=label]').val(),
                $('input[name=price]').val(),
                $('input[name=number_place]').val());
    });

}